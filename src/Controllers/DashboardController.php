<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\ProjectRepository;
use App\Repositories\SkillRepository;
use App\Repositories\UserRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ContactRepository;
use App\Middleware\AuthMiddleware;

/**
 * DashboardController
 * Gère le tableau de bord admin
 */
class DashboardController
{
    private ProjectRepository $projectRepository;
    private SkillRepository $skillRepository;
    private UserRepository $userRepository;
    private CategoryRepository $categoryRepository;
    private ContactRepository $contactRepository;

    public function __construct()
    {
        $db = Database::getInstance()->connect();

        $this->projectRepository = new ProjectRepository($db);
        $this->skillRepository = new SkillRepository($db);
        $this->userRepository = new UserRepository($db);
        $this->categoryRepository = new CategoryRepository($db);
        $this->contactRepository = new ContactRepository($db);
    }

    /**
     * Page du dashboard
     */
    public function index(): void
    {
        // Vérifier l'authentification
        AuthMiddleware::requireAuth();

        // Récupérer les statistiques
        $projectsCount = $this->projectRepository->count();
        $skillsCount = $this->skillRepository->count();
        $categoriesCount = $this->categoryRepository->count();
        $contactsCount = $this->contactRepository->count();

        // Récupérer les 5 derniers messages
        $recentMessages = $this->contactRepository->getLatest(5);

        // Récupérer les catégories pour le select
        $categories = $this->categoryRepository->all('name ASC');

        // Récupérer toutes les skills pour le select
        $skills = $this->skillRepository->all('name ASC');

        // Charger la vue
        $template = 'dashboard';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }
}
