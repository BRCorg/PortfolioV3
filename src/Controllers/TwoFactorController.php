<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\UserRepository;
use App\Middleware\AuthMiddleware;
use App\Core\TwoFactorAuth;
use App\Core\SecurityLogger;

/**
 * TwoFactorController
 * Gestion de l'authentification à deux facteurs (2FA)
 */
class TwoFactorController
{
    private UserRepository $userRepository;
    private SecurityLogger $securityLogger;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();

        $this->userRepository = new UserRepository($db);
        $this->securityLogger = new SecurityLogger();
    }

    /**
     * Afficher la page de configuration 2FA
     */
    public function showSetup(): void
    {
        AuthMiddleware::requireAuth();

        // Récupérer l'utilisateur depuis la BDD pour avoir les colonnes à jour
        $userId = $_SESSION['user']['id'] ?? null;
        
        if (!$userId) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL']);
            exit;
        }

        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL']);
            exit;
        }

        // Vérifier si le 2FA n'est pas déjà activé
        if (isset($user['two_factor_enabled']) && $user['two_factor_enabled']) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard?error=already_enabled');
            exit;
        }

        // Générer un nouveau secret
        $secret = TwoFactorAuth::generateSecret();
        $_SESSION['temp_2fa_secret'] = $secret;

        // Générer l'URL du QR code
        $qrCodeUrl = TwoFactorAuth::getQRCodeUrl($secret, $user['email'], 'Portfolio Beran');

        $template = 'setup2fa';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Activer le 2FA après vérification du code
     *
     * Processus complet d'activation du 2FA :
     * 1. Vérification que le code TOTP saisi par l'utilisateur est valide
     * 2. Génération de 10 codes de backup à usage unique
     * 3. Sauvegarde du secret et des codes hashés en base de données
     * 4. Logging de l'activation pour traçabilité sécurité
     * 5. Affichage des codes de backup à l'utilisateur (une seule fois)
     *
     * @return void Redirige vers la page d'affichage des codes de backup
     */
    public function enable(): void
    {
        AuthMiddleware::requireAuth();

        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL']);
            exit;
        }

        $user = $this->userRepository->find($userId);
        $code = $_POST['code'] ?? '';
        $secret = $_POST['secret'] ?? '';

        // ÉTAPE 1 : Vérifier que le secret correspond bien à celui stocké temporairement en session
        // Cette vérification empêche l'utilisation d'un secret arbitraire
        if ($secret !== ($_SESSION['temp_2fa_secret'] ?? '')) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/setup?error=invalid_secret');
            exit;
        }

        // ÉTAPE 2 : Vérifier que le code TOTP saisi est valide avec ce secret
        // Cela prouve que l'utilisateur a bien scanné le QR code et peut générer des codes
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/setup?error=invalid_code');
            exit;
        }

        // ÉTAPE 3 : Générer 10 codes de backup à usage unique
        // Ces codes permettent de se connecter si l'utilisateur perd son téléphone
        $backupCodes = TwoFactorAuth::generateBackupCodes(10);

        // ÉTAPE 4 : Hasher les codes de backup avant stockage en BDD
        // On ne stocke jamais les codes en clair pour des raisons de sécurité
        $hashedBackupCodes = array_map([TwoFactorAuth::class, 'hashBackupCode'], $backupCodes);

        // ÉTAPE 5 : Enregistrement en base de données
        // Sauvegarde le secret 2FA et les codes de backup hashés
        $this->userRepository->enable2FA(
            $user['id'],
            $secret,
            json_encode($hashedBackupCodes)
        );

        // ÉTAPE 6 : Logging de sécurité
        // Enregistrer l'activation du 2FA pour audit et traçabilité
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logSuspiciousActivity('2FA_ENABLED', $ip, [
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        // ÉTAPE 7 : Mettre à jour les informations utilisateur en session
        $updatedUser = $this->userRepository->find($user['id']);
        $_SESSION['user'] = $updatedUser;

        // ÉTAPE 8 : Stocker temporairement les codes de backup en session
        // Les codes en clair ne seront affichés qu'une seule fois à l'utilisateur
        $_SESSION['backup_codes'] = $backupCodes;

        // ÉTAPE 9 : Nettoyer le secret temporaire (plus nécessaire)
        unset($_SESSION['temp_2fa_secret']);

        // ÉTAPE 10 : Rediriger vers la page d'affichage des codes de backup
        // L'utilisateur DOIT sauvegarder ces codes maintenant
        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/backup-codes');
        exit;
    }

    /**
     * Afficher les codes de backup après activation
     */
    public function showBackupCodes(): void
    {
        AuthMiddleware::requireAuth();

        if (!isset($_SESSION['backup_codes'])) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard');
            exit;
        }

        $backupCodes = $_SESSION['backup_codes'];
        $template = 'backup2fa';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Confirmation des codes de backup (les effacer de la session)
     */
    public function confirmBackupCodes(): void
    {
        AuthMiddleware::requireAuth();
        unset($_SESSION['backup_codes']);
        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard?success=2fa_enabled');
        exit;
    }

    /**
     * Afficher la page de vérification 2FA (lors du login)
     */
    public function showVerify(): void
    {
        // Vérifier qu'une tentative de connexion 2FA est en cours
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL']);
            exit;
        }

        $template = 'verify2fa';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Vérifier le code 2FA lors du login
     */
    public function verify(): void
    {
        $code = $_POST['code'] ?? '';
        $userId = $_SESSION['pending_2fa_user_id'] ?? null;

        if (!$userId) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=expired');
            exit;
        }

        // Récupérer l'utilisateur
        $user = $this->userRepository->find($userId);

        if (!$user || !$user['two_factor_enabled']) {
            unset($_SESSION['pending_2fa_user_id']);
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=invalid');
            exit;
        }

        // Vérifier le code
        if (!TwoFactorAuth::verifyCode($user['two_factor_secret'], $code)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $this->securityLogger->logLoginFailed($user['email'], $ip, '2FA code invalid');
            
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/verify?error=invalid_code');
            exit;
        }

        // Connexion réussie
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logLoginSuccess($user['email'], $ip);

        unset($_SESSION['pending_2fa_user_id']);
        AuthMiddleware::login($user);

        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard');
        exit;
    }

    /**
     * Vérifier un code de backup lors de la connexion
     *
     * Les codes de backup permettent de se connecter si l'utilisateur n'a plus accès
     * à son application 2FA (téléphone perdu, réinitialisation, etc.)
     *
     * Fonctionnement :
     * - Chaque code ne peut être utilisé qu'une seule fois (consommation)
     * - Les codes sont stockés hashés en BDD (comme les mots de passe)
     * - Après utilisation, le code est supprimé définitivement
     * - L'utilisateur est averti du nombre de codes restants
     *
     * @return void Redirige vers le dashboard en cas de succès
     */
    public function verifyBackup(): void
    {
        $backupCode = $_POST['backup_code'] ?? '';
        $userId = $_SESSION['pending_2fa_user_id'] ?? null;

        if (!$userId) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=expired');
            exit;
        }

        // ÉTAPE 1 : Récupérer l'utilisateur depuis la BDD
        $user = $this->userRepository->find($userId);

        if (!$user || !$user['two_factor_enabled']) {
            unset($_SESSION['pending_2fa_user_id']);
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '?error=invalid');
            exit;
        }

        // ÉTAPE 2 : Récupérer tous les codes de backup hashés stockés en BDD
        // Les codes sont stockés en JSON comme un tableau de hashes
        $backupCodes = json_decode($user['two_factor_backup_codes'], true) ?? [];
        $validCode = false;
        $usedIndex = null;

        // ÉTAPE 3 : Parcourir tous les codes hashés pour trouver une correspondance
        // On ne peut pas comparer directement car les codes sont hashés (comme password_verify)
        foreach ($backupCodes as $index => $hashedCode) {
            if (TwoFactorAuth::verifyBackupCode($backupCode, $hashedCode)) {
                $validCode = true;
                $usedIndex = $index; // Enregistrer l'index du code valide pour le supprimer
                break;
            }
        }

        // ÉTAPE 4 : Si aucun code ne correspond, bloquer la connexion
        if (!$validCode) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $this->securityLogger->logLoginFailed($user['email'], $ip, 'Invalid backup code');

            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/verify?error=invalid_code');
            exit;
        }

        // ÉTAPE 5 : Supprimer le code de backup utilisé (consommation)
        // Les codes de backup ne peuvent être utilisés qu'une seule fois
        unset($backupCodes[$usedIndex]);
        $backupCodes = array_values($backupCodes); // Réindexer le tableau pour éviter les trous

        // ÉTAPE 6 : Mettre à jour les codes restants en BDD
        $this->userRepository->updateBackupCodes($user['id'], json_encode($backupCodes));

        // ÉTAPE 7 : Logging de sécurité
        // L'utilisation d'un code de backup est un événement important à tracer
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logSuspiciousActivity('2FA_BACKUP_CODE_USED', $ip, [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'remaining_codes' => count($backupCodes)
        ]);

        // ÉTAPE 8 : Connexion réussie - créer la session utilisateur
        unset($_SESSION['pending_2fa_user_id']);
        AuthMiddleware::login($user);

        // ÉTAPE 9 : Rediriger avec un avertissement indiquant le nombre de codes restants
        // Si codes < 3, l'utilisateur devrait régénérer de nouveaux codes
        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard?warning=backup_used&remaining=' . count($backupCodes));
        exit;
    }

    /**
     * Désactiver le 2FA
     */
    public function disable(): void
    {
        AuthMiddleware::requireAuth();

        $user = $_SESSION['user'];
        $password = $_POST['password'] ?? '';

        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard?error=invalid_password');
            exit;
        }

        // Désactiver le 2FA
        $this->userRepository->disable2FA($user['id']);

        // Logger la désactivation
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logSuspiciousActivity('2FA_DISABLED', $ip, [
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        // Mettre à jour la session
        $updatedUser = $this->userRepository->find($user['id']);
        $_SESSION['user'] = $updatedUser;

        header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/dashboard?success=2fa_disabled');
        exit;
    }
}
