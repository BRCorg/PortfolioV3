<?php

namespace App\Controllers;

use Config\Database;
use App\Repositories\ProjectRepository;
use App\Repositories\CategoryRepository;
use App\Middleware\AuthMiddleware;

/**
 * ProjectController
 * Gère le CRUD des projets
 */
class ProjectController
{
    private ProjectRepository $projectRepository;
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();

        $this->projectRepository = new ProjectRepository($db);
        $this->categoryRepository = new CategoryRepository($db);
    }

    /**
     * Liste des projets (admin)
     */
    public function list(): void
    {
        AuthMiddleware::requireAuth();

        // Désactiver le cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        $projects = $this->projectRepository->all('display_order ASC');

        // Récupérer le nom de la catégorie pour chaque projet
        foreach ($projects as $key => $project) {
            $completeProject = $this->projectRepository->findWithCategory($project['id']);
            $projects[$key]['category_name'] = $completeProject['category_name'] ?? 'Sans catégorie';
        }

        $template = 'listProjects';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }

    /**
     * Créer un projet (AJAX)
     */
    public function create(): void
    {
        AuthMiddleware::requireAuth();

        try {
            // Vérifier le token CSRF
            $token = $_POST['csrf_token'] ?? '';
            
            // Debug temporaire
            error_log('Token reçu: ' . $token);
            error_log('Token session: ' . ($_SESSION['csrf_token'] ?? 'NON DEFINI'));
            
            if (!AuthMiddleware::verifyCsrfToken($token)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token de sécurité invalide',
                    'debug' => [
                        'token_recu' => $token,
                        'token_session' => $_SESSION['csrf_token'] ?? 'NON DEFINI',
                        'session_id' => session_id()
                    ]
                ]);
                exit;
            }

            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'long_description' => $_POST['long_description'] ?? $_POST['description_long'] ?? $_POST['description'] ?? '',
                'category_id' => $_POST['category_id'] ?? null,
                'status' => 'published',
                'is_featured' => 0
            ];

        // Gérer l'upload de l'image
        $uploadError = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Valider le fichier uploadé
            $validation = \validateImageUpload($_FILES['image']);

            if (!$validation['valid']) {
                $uploadError = $validation['error'];
            } else {
                $uploadDir = __DIR__ . '/../../public/uploads/projects/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Générer un nom de fichier sécurisé
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fileName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $data['image'] = '/uploads/projects/' . $fileName;
                } else {
                    $uploadError = 'Échec du déplacement du fichier';
                }
            }
        }

        // Récupérer les skills (si fournis)
        $skillIds = $_POST['skill_ids'] ?? [];

        $projectId = $this->projectRepository->createProject($data, $skillIds);

        // Gérer l'upload du carousel (images supplémentaires)
        $galleryErrors = [];
        if ($projectId > 0 && isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/projects/';
            
            foreach ($_FILES['gallery']['name'] as $key => $name) {
                if ($_FILES['gallery']['error'][$key] === UPLOAD_ERR_OK) {
                    // Validation de chaque image
                    $fileData = [
                        'name' => $_FILES['gallery']['name'][$key],
                        'type' => $_FILES['gallery']['type'][$key],
                        'tmp_name' => $_FILES['gallery']['tmp_name'][$key],
                        'error' => $_FILES['gallery']['error'][$key],
                        'size' => $_FILES['gallery']['size'][$key]
                    ];
                    
                    $validation = \validateImageUpload($fileData);
                    
                    if ($validation['valid']) {
                        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $fileName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['gallery']['tmp_name'][$key], $targetPath)) {
                            // Ajouter l'image au projet avec le chemin complet
                            $this->projectRepository->addProjectImage($projectId, '/uploads/projects/' . $fileName, $key + 1);
                        } else {
                            $galleryErrors[] = "Échec upload: $name";
                        }
                    } else {
                        $galleryErrors[] = $validation['error'] . ": $name";
                    }
                }
            }
        }

        $message = $projectId > 0 ? 'Projet créé avec succès' : 'Erreur lors de la création';
        if ($projectId > 0 && $uploadError) {
            $message .= ' (⚠️ Image principale: ' . $uploadError . ')';
        }
        if (!empty($galleryErrors)) {
            $message .= ' (⚠️ Carousel: ' . implode(', ', $galleryErrors) . ')';
        }

        echo json_encode([
            'success' => $projectId > 0,
            'message' => $message,
            'project_id' => $projectId,
            'upload_error' => $uploadError
        ]);
        
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour un projet
     */
    public function update(int $id): void
    {
        AuthMiddleware::requireAuth();

        // Vérifier le token CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!AuthMiddleware::verifyCsrfToken($token)) {
            echo json_encode([
                'success' => false,
                'message' => 'Token de sécurité invalide'
            ]);
            exit;
        }

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? null,
        ];

        if (isset($_POST['status'])) {
            $data['status'] = $_POST['status'];
        }

        if (isset($_POST['is_featured'])) {
            $data['is_featured'] = (int) $_POST['is_featured'];
        }

        // Gérer l'upload de l'image
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Valider le fichier uploadé
            $validation = validateImageUpload($_FILES['image']);

            if ($validation['valid']) {
                $uploadDir = __DIR__ . '/../../public/uploads/projects/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Générer un nom de fichier sécurisé
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fileName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $data['image'] = '/uploads/projects/' . $fileName;
                }
            }
        }

        $skillIds = isset($_POST['skill_ids']) ? json_decode($_POST['skill_ids'], true) : null;

        $updated = $this->projectRepository->updateProject($id, $data, $skillIds);

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? 'Projet mis à jour' : 'Erreur lors de la mise à jour'
        ]);
    }

    /**
     * Supprimer un projet
     */
    public function delete(int $id): void
    {
        AuthMiddleware::requireAuth();

        $deleted = $this->projectRepository->delete($id);

        if ($deleted) {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/projects');
        } else {
            header('Location: ' . $_ENV['ADMIN_SECRET_URL'] . '/projects?error=delete_failed');
        }
        exit;
    }
}
