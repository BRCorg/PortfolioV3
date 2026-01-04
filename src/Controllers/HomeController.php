<?php

namespace App\Controllers;

use config\Database;
use App\Repositories\ProjectRepository;
use App\Repositories\SkillRepository;
use App\Repositories\UserRepository;
use App\Repositories\CategoryRepository;

/**
 * HomeController
 * Gère l'affichage de la page d'accueil
 */
class HomeController
{
    private ProjectRepository $projectRepository;
    private SkillRepository $skillRepository;
    private UserRepository $userRepository;
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        $database = new Database();
        $db = $database->connect();

        $this->projectRepository = new ProjectRepository($db);
        $this->skillRepository = new SkillRepository($db);
        $this->userRepository = new UserRepository($db);
        $this->categoryRepository = new CategoryRepository($db);
    }

    /**
     * Page d'accueil
     */
    public function index(): void
    {
        // Récupérer tous les projets publiés
        $allProjects = $this->projectRepository->getPublished('display_order ASC');
        $projects = [];

        foreach ($allProjects as $project) {
            $completeProject = $this->projectRepository->findComplete($project['id']);
            $projects[] = $completeProject;
        }

        // Récupérer toutes les compétences (ordonnées)
        $skills = $this->skillRepository->all('display_order ASC');

        // Récupérer les infos utilisateur (admin)
        $user = $this->userRepository->find(1); // ID 1 = toi
        if (!$user) {
            $user = [
                'name' => 'Berancan Guven',
                'email' => '',
                'src' => 'assets/img/profile.jpg',
                'description' => '',
                'birthdate' => ''
            ];
        }

        // Récupérer toutes les catégories
        $categories = $this->categoryRepository->all('name ASC');

        // SEO Meta Tags
        $pageTitle = 'Berancan Guven - Développeur Web Full Stack | Portfolio';
        $pageDescription = 'Étudiant en 2ème année Bachelor Dev Web Full Stack à la 3W Academy. Découvrez mes projets en PHP, Symfony, React et JavaScript. En recherche de stage 4 mois (avril-juillet 2025).';
        $canonicalUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/';
        $ogTitle = 'Berancan Guven - Développeur Web Full Stack';
        $ogDescription = 'Portfolio de Berancan Guven, développeur web full stack junior spécialisé en PHP, Symfony et React.';
        $ogImage = 'https://' . $_SERVER['HTTP_HOST'] . '/img/PhotoDeProfilPF2.png';

        // Charger la vue
        $template = 'home';
        include __DIR__ . '/../Views/layout.phtml';
    }

    /**
     * Afficher les détails d'un projet
     */
    public function projectDetails(int $id): void
    {
        $project = $this->projectRepository->findComplete($id);

        if (!$project) {
            header('HTTP/1.0 404 Not Found');
            include __DIR__ . '/../Views/error404.phtml';
            exit;
        }

        // Récupérer tous les projets pour le menu
        $allProjects = $this->projectRepository->getPublished('display_order ASC');
        $projects = [];
        foreach ($allProjects as $proj) {
            $projects[] = $this->projectRepository->findComplete($proj['id']);
        }

        // SEO Meta Tags pour le projet
        $pageTitle = htmlspecialchars($project['title']) . ' - Projet de Guven Berancan';
        $pageDescription = substr(strip_tags($project['description']), 0, 155) . '...';
        $canonicalUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/project/' . $id;
        $ogTitle = htmlspecialchars($project['title']);
        $ogDescription = substr(strip_tags($project['description']), 0, 200);
        $ogImage = !empty($project['images'][0]['file_path']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/projects/' . $project['images'][0]['file_path'] : 'https://' . $_SERVER['HTTP_HOST'] . '/img/PhotoDeProfilPF2.png';

        $template = 'projectDetails';
        include __DIR__ . '/../Views/layout.phtml';
    }
}
