<?php

/**
 * Fonctions helper globales
 */

/**
 * Échapper les caractères HTML pour éviter les failles XSS
 */
function e(?string $string): string
{
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Afficher de manière sécurisée (alias de e())
 */
function safe(?string $string): string
{
    return e($string);
}

/**
 * Valider un fichier uploadé (image)
 *
 * @param array $file Le tableau $_FILES['nom']
 * @param int $maxSize Taille maximum en octets (défaut: 5MB)
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateImageUpload(array $file, int $maxSize = 5242880): array
{
    // Vérifier qu'il y a bien un fichier
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Aucun fichier uploadé'];
    }

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (limite serveur)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux',
            UPLOAD_ERR_PARTIAL => 'Upload incomplet',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture',
            UPLOAD_ERR_EXTENSION => 'Extension bloquée'
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Erreur inconnue'];
    }

    // Vérifier la taille
    if ($file['size'] > $maxSize) {
        $maxMB = round($maxSize / 1048576, 1);
        return ['valid' => false, 'error' => "Image trop grande (max {$maxMB}MB)"];
    }

    // Vérifier que le fichier est bien une image via son type MIME
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        return ['valid' => false, 'error' => 'Format non autorisé (JPG, PNG, WebP uniquement)'];
    }

    // Vérifier l'extension du fichier
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Extension non autorisée'];
    }

    // Tout est OK
    return ['valid' => true, 'error' => null];
}
