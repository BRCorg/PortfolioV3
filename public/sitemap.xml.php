<?php

/**
 * Génération dynamique du sitemap.xml
 * Listage de toutes les pages publiques du portfolio
 */

require_once __DIR__ . '/../bootstrap.php';

use config\Database;
use App\Repositories\ProjectRepository;

header('Content-Type: application/xml; charset=utf-8');

$db = Database::getInstance()->connect();
$projectRepository = new ProjectRepository($db);

// URL de base du site
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'];

// Date actuelle pour lastmod
$currentDate = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Page d'accueil -->
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <lastmod><?= $currentDate ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Section Projets -->
    <url>
        <loc><?= $baseUrl ?>/#projects</loc>
        <lastmod><?= $currentDate ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Section Compétences / Parcours -->
    <url>
        <loc><?= $baseUrl ?>/#skills</loc>
        <lastmod><?= $currentDate ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Section Témoignages -->
    <url>
        <loc><?= $baseUrl ?>/#testimonials</loc>
        <lastmod><?= $currentDate ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Section Contact -->
    <url>
        <loc><?= $baseUrl ?>/#contact</loc>
        <lastmod><?= $currentDate ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.8</priority>
    </url>

    <?php
    // Récupérer tous les projets publiés
    $projects = $projectRepository->getPublished('display_order ASC');
    
    foreach ($projects as $project):
        // Date de modification du projet (ou date actuelle si non disponible)
        $lastmod = !empty($project['updated_at']) ? date('Y-m-d', strtotime($project['updated_at'])) : $currentDate;
    ?>
    <!-- Projet: <?= htmlspecialchars($project['title']) ?> -->
    <url>
        <loc><?= $baseUrl ?>/project/<?= $project['slug'] ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

</urlset>
