<?php

namespace App\Controllers;

use Config\Database;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\Category;
use App\Models\Contact;
use App\Middleware\AuthMiddleware;

/**
 * DashboardController
 * G�re le tableau de bord admin
 */
class DashboardController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Page du dashboard
     */
    public function index(): void
    {
        // V�rifier l'authentification
        AuthMiddleware::requireAuth();

        // Récupérer les statistiques
        $projectModel = new Project($this->db);
        $skillModel = new Skill($this->db);
        $userModel = new User($this->db);
        $categoryModel = new Category($this->db);
        $contactModel = new Contact($this->db);

        $projectsCount = $projectModel->count();
        $skillsCount = $skillModel->count();
        $categoriesCount = $categoryModel->count();
        $contactsCount = $contactModel->count();

        // Récupérer les 5 derniers messages
        $recentMessages = $contactModel->getLatest(5);

        // R�cup�rer les cat�gories pour le select
        $categories = $categoryModel->all('name ASC');

        // R�cup�rer toutes les skills pour le select
        $skills = $skillModel->all('name ASC');

        // Charger la vue
        $template = 'dashboard';
        include __DIR__ . '/../Views/admin-layout.phtml';
    }
}
