<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\ContactRepository;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimiter;
use App\Core\SecurityLogger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * ContactController
 * Gère le formulaire de contact
 */
class ContactController
{
    private ContactRepository $contactRepository;
    private SecurityLogger $securityLogger;

    public function __construct()
    {
        $db = Database::getInstance()->connect();

        $this->contactRepository = new ContactRepository($db);
        $this->securityLogger = new SecurityLogger();
    }

    /**
     * Traiter le formulaire de contact avec protection anti-spam multi-couches
     *
     * Système de sécurité en 7 couches :
     * 1. Rate limiting par IP (3 messages max par heure)
     * 2. Validation token CSRF (protection contre Cross-Site Request Forgery)
     * 3. Validation des champs obligatoires
     * 4. Rate limiting par email (2 messages max par 30min)
     * 5. Détection de contenu spam (mots-clés, liens)
     * 6. Sauvegarde en base de données (avec IP et user-agent)
     * 7. Envoi d'email de notification à l'admin
     *
     * @return void Retourne une réponse JSON avec le statut d'envoi
     */
    public function submit(): void
    {
        // COUCHE 1 : Rate limiting par adresse IP
        // Limite : 3 tentatives par heure pour éviter le spam automatisé
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::attempt("contact_ip_{$clientIP}", 3, 60)) {
            $this->securityLogger->logRateLimitBlock('contact_ip', $clientIP, $clientIP);
            echo json_encode([
                'success' => false,
                'message' => 'Trop de messages envoyés. Réessayez dans 1 heure.'
            ]);
            exit;
        }

        // COUCHE 2 : Vérification du token CSRF
        // Empêche les soumissions de formulaire depuis des sites externes
        $token = $_POST['csrf_token'] ?? '';
        if (!AuthMiddleware::verifyCsrfToken($token)) {
            $this->securityLogger->logCsrfAttempt($clientIP);
            echo json_encode([
                'success' => false,
                'message' => 'Token de sécurité invalide'
            ]);
            exit;
        }

        // Nettoyage des données (trim pour enlever les espaces superflus)
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => trim($_POST['message'] ?? '')
        ];

        // COUCHE 3 : Validation des champs obligatoires
        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Tous les champs sont requis'
            ]);
            exit;
        }

        // Validation du format email (RFC 822)
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email invalide'
            ]);
            exit;
        }

        // COUCHE 4 : Rate limiting par adresse email
        // Limite : 2 messages max par 30 minutes par email
        // Empêche qu'un utilisateur spam avec la même adresse email
        if (!RateLimiter::attempt("contact_email_{$data['email']}", 2, 30)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vous avez déjà envoyé un message récemment.'
            ]);
            exit;
        }

        // COUCHE 5 : Détection de contenu spam
        // Analyse le message pour détecter des patterns de spam (mots-clés, liens multiples)
        if ($this->isSpam($data)) {
            $this->securityLogger->logSpamDetection($clientIP, $data);
            echo json_encode([
                'success' => false,
                'message' => 'Message détecté comme spam'
            ]);
            exit;
        }

        // COUCHE 6 : Sauvegarde en base de données
        // Le repository enregistre aussi l'IP et le user-agent pour traçabilité
        $contactId = $this->contactRepository->create($data);

        if ($contactId > 0) {
            // COUCHE 7 : Envoi de l'email de notification à l'administrateur
            // Utilise PHPMailer avec SMTP pour fiabilité
            $emailSent = $this->sendNotificationEmail($data);

            echo json_encode([
                'success' => true,
                'message' => 'Message envoyé avec succès !',
                'email_sent' => $emailSent
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message'
            ]);
        }
    }

    /**
     * Détection de spam basée sur l'analyse heuristique du contenu
     *
     * Méthode de détection simple mais efficace utilisant deux approches :
     * 1. Liste de mots-clés typiques du spam (blacklist)
     * 2. Comptage des liens HTTP (les spammeurs incluent souvent de nombreux liens)
     *
     * @param array $data Données du formulaire (name, email, subject, message)
     * @return bool True si le message est considéré comme spam, False sinon
     */
    private function isSpam(array $data): bool
    {
        // Liste de mots-clés couramment utilisés dans les spams
        // Cette liste peut être étendue selon les patterns observés
        $spamWords = ['viagra', 'casino', 'bitcoin', 'crypto', 'loan', 'free money', 'click here', 'urgent'];

        // Concaténer le message et le sujet pour analyse globale
        // Conversion en minuscules pour une comparaison insensible à la casse
        $content = strtolower($data['message'] . ' ' . $data['subject']);

        // RÈGLE 1 : Vérifier la présence de mots-clés spam
        foreach ($spamWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                return true; // Mot spam détecté
            }
        }

        // RÈGLE 2 : Compter le nombre de liens HTTP/HTTPS
        // Les messages légitimes contiennent rarement plus de 2 liens
        // Les spammeurs incluent souvent de nombreux liens vers des sites malveillants
        if (substr_count($content, 'http') > 2) {
            return true; // Trop de liens détectés
        }

        // Le message passe tous les filtres, probablement légitime
        return false;
    }

    /**
     * Envoyer email de notification
     */
    private function sendNotificationEmail(array $data): bool
    {
        if (empty($_ENV['MAIL_USERNAME']) || empty($_ENV['ADMIN_EMAIL'])) {
            error_log('Configuration email manquante');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? throw new \RuntimeException('MAIL_HOST non défini dans .env');
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
            
            // Destinataires
            $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME'] ?? 'Portfolio');
            $mail->addAddress($_ENV['ADMIN_EMAIL']);
            $mail->addReplyTo($data['email'], $data['name']);
            
            // Contenu
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = '🔔 Nouveau message: ' . htmlspecialchars($data['subject'] ?? 'Contact');
            
            $mail->Body = $this->getEmailTemplate($data);
            
            return $mail->send();
            
        } catch (Exception $e) {
            error_log("Erreur email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Template HTML pour l'email
     */
    private function getEmailTemplate(array $data): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #314473; border-bottom: 2px solid #314473; padding-bottom: 10px;'>
                📬 Nouveau message de contact
            </h2>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <p><strong>👤 Nom:</strong> " . htmlspecialchars($data['name']) . "</p>
                <p><strong>📧 Email:</strong> " . htmlspecialchars($data['email']) . "</p>
                <p><strong>📝 Sujet:</strong> " . htmlspecialchars($data['subject'] ?? 'Pas de sujet') . "</p>
                <p><strong>🕒 Date:</strong> " . date('d/m/Y à H:i') . "</p>
            </div>
            
            <div style='background: white; padding: 20px; border-left: 4px solid #314473; margin: 20px 0;'>
                <h3>💬 Message:</h3>
                <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
            </div>
            
            <p style='color: #666; font-size: 0.9em; margin-top: 30px;'>
                Envoyé depuis votre portfolio - " . date('d/m/Y à H:i:s') . "
            </p>
        </div>
        ";
    }

    /**
     * Liste des messages (admin)
     */
    public function list(): void
    {
        AuthMiddleware::requireAuth();

        $messages = $this->contactRepository->all('created_at DESC');

        $template = 'listContacts';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(int $id): void
    {
        AuthMiddleware::requireAuth();

        $updated = $this->contactRepository->markAsRead($id);

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? 'Message marqué comme lu' : 'Erreur'
        ]);
    }

    /**
     * Supprimer un message
     */
    public function delete(int $id): void
    {
        AuthMiddleware::requireAuth();

        $deleted = $this->contactRepository->delete($id);

        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'Message supprimé' : 'Erreur'
        ]);
    }
}
