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

        // Vérifier que le secret correspond à celui en session
        if ($secret !== ($_SESSION['temp_2fa_secret'] ?? '')) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/setup?error=invalid_secret');
            exit;
        }

        // Vérifier le code
        if (!TwoFactorAuth::verifyCode($secret, $code)) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/setup?error=invalid_code');
            exit;
        }

        // Générer les codes de backup
        $backupCodes = TwoFactorAuth::generateBackupCodes(10);
        $hashedBackupCodes = array_map([TwoFactorAuth::class, 'hashBackupCode'], $backupCodes);

        // Sauvegarder le 2FA en base
        $this->userRepository->enable2FA(
            $user['id'],
            $secret,
            json_encode($hashedBackupCodes)
        );

        // Logger l'activation
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logSuspiciousActivity('2FA_ENABLED', $ip, [
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        // Mettre à jour la session
        $updatedUser = $this->userRepository->find($user['id']);
        $_SESSION['user'] = $updatedUser;

        // Stocker les codes de backup en session pour affichage
        $_SESSION['backup_codes'] = $backupCodes;

        // Nettoyer le secret temporaire
        unset($_SESSION['temp_2fa_secret']);

        // Rediriger vers la page de codes de backup
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
     * Vérifier un code de backup
     */
    public function verifyBackup(): void
    {
        $backupCode = $_POST['backup_code'] ?? '';
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

        // Vérifier le code de backup
        $backupCodes = json_decode($user['two_factor_backup_codes'], true) ?? [];
        $validCode = false;
        $usedIndex = null;

        foreach ($backupCodes as $index => $hashedCode) {
            if (TwoFactorAuth::verifyBackupCode($backupCode, $hashedCode)) {
                $validCode = true;
                $usedIndex = $index;
                break;
            }
        }

        if (!$validCode) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $this->securityLogger->logLoginFailed($user['email'], $ip, 'Invalid backup code');
            
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/2fa/verify?error=invalid_code');
            exit;
        }

        // Supprimer le code de backup utilisé
        unset($backupCodes[$usedIndex]);
        $backupCodes = array_values($backupCodes); // Réindexer
        $this->userRepository->updateBackupCodes($user['id'], json_encode($backupCodes));

        // Connexion réussie
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->securityLogger->logSuspiciousActivity('2FA_BACKUP_CODE_USED', $ip, [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'remaining_codes' => count($backupCodes)
        ]);

        unset($_SESSION['pending_2fa_user_id']);
        AuthMiddleware::login($user);

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
