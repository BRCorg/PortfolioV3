<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\CategoryRepository;
use App\Middleware\AuthMiddleware;

/**
 * CategoryController
 * Gère le CRUD des catégories
 */
class CategoryController
{
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        $db = Database::getInstance()->connect();

        $this->categoryRepository = new CategoryRepository($db);
    }

    /**
     * Liste des catégories (admin)
     */
    public function list(): void
    {
        AuthMiddleware::requireAuth();

        $categories = $this->categoryRepository->all('name ASC');

        $template = 'listCategorie';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Créer une catégorie (AJAX)
     */
    public function create(): void
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
            'description' => $_POST['description'] ?? ''
        ];

        $categoryId = $this->categoryRepository->create($data);

        echo json_encode([
            'success' => $categoryId > 0,
            'message' => $categoryId > 0 ? 'Catégorie créée' : 'Erreur lors de la création',
            'category_id' => $categoryId
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): void
    {
        AuthMiddleware::requireAuth();

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            header('HTTP/1.0 404 Not Found');
            echo "Catégorie non trouvée";
            exit;
        }

        $template = 'editCategorie';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Mettre à jour une catégorie
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
            'description' => $_POST['description'] ?? ''
        ];

        $updated = $this->categoryRepository->update($id, $data);

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? 'Catégorie mise à jour' : 'Erreur'
        ]);
    }

    /**
     * Supprimer une catégorie
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

        $deleted = $this->categoryRepository->delete($id);

        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'Catégorie supprimée' : 'Erreur'
        ]);
    }
}
