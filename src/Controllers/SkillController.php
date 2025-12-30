<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\SkillRepository;
use App\Middleware\AuthMiddleware;

/**
 * SkillController
 * Gère le CRUD des compétences
 */
class SkillController
{
    private SkillRepository $skillRepository;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();

        $this->skillRepository = new SkillRepository($db);
    }

    /**
     * Liste des skills (admin)
     */
    public function list(): void
    {
        AuthMiddleware::requireAuth();

        $skills = $this->skillRepository->all('display_order ASC');

        $template = 'listSkill';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Créer une skill (AJAX)
     */
    public function create(): void
    {
        AuthMiddleware::requireAuth();

        try {
            // Vérifier le token CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!AuthMiddleware::verifyCsrfToken($token)) {
                echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
                exit;
            }

            $data = [
                'name' => $_POST['name'] ?? '',
                'slug' => $this->generateSlug($_POST['name'] ?? '')
            ];

            $skillId = $this->skillRepository->create($data);

            echo json_encode([
                'success' => $skillId > 0,
                'message' => $skillId > 0 ? 'Compétence créée' : 'Erreur lors de la création',
                'skill_id' => $skillId
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Mettre à jour une skill
     */
    public function update(int $id): void
    {
        AuthMiddleware::requireAuth();

        // Vérifier le token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!AuthMiddleware::verifyCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            exit;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
        ];

        if (isset($_POST['level'])) {
            $data['level'] = $_POST['level'];
        }

        if (isset($_POST['category'])) {
            $data['category'] = $_POST['category'];
        }

        if (isset($_POST['display_order'])) {
            $data['display_order'] = (int) $_POST['display_order'];
        }

        $updated = $this->skillRepository->update($id, $data);

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? 'Compétence mise à jour' : 'Erreur'
        ]);
    }

    /**
     * Supprimer une skill
     */
    public function delete(int $id): void
    {
        AuthMiddleware::requireAuth();

        // Vérifier le token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!AuthMiddleware::verifyCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            exit;
        }

        $deleted = $this->skillRepository->delete($id);

        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'Compétence supprimée' : 'Erreur'
        ]);
    }

    /**
     * Générer un slug à partir d'un nom
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
