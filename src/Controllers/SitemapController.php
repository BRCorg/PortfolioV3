<?php

namespace App\Controllers;

/**
 * SitemapController
 * Génération du sitemap XML
 */
class SitemapController
{
    /**
     * Générer le sitemap
     */
    public function generate(): void
    {
        include __DIR__ . '/../../public/sitemap.xml.php';
        exit;
    }
}
