#!/bin/bash

# Script de déploiement pour VPS OVH
# Portfolio V2 - Guven Berancan

echo "🚀 Déploiement Portfolio V2 sur VPS OVH"
echo "========================================"

# 1. Installation des dépendances PHP
echo "📦 Installation des dépendances Composer..."
composer install --no-dev --optimize-autoloader

# 2. Compilation SCSS en production
echo "🎨 Compilation SCSS en mode production..."
sass scss/style.scss public/css/style.css --style=compressed --no-source-map

# 3. Permissions des dossiers
echo "🔒 Configuration des permissions..."
chmod -R 755 public/
chmod -R 775 public/uploads/
chmod -R 775 logs/
chmod 600 .env

# 4. Nettoyage
echo "🧹 Nettoyage des fichiers temporaires..."
rm -rf .phpunit.cache
rm -rf tests/
rm -f phpunit.xml
rm -f composer.lock
rm -f *.bat
rm -f README-SASS.md

# 5. Vérification .env
echo "⚙️ Vérification de la configuration..."
if [ ! -f .env ]; then
    echo "❌ ERREUR: Fichier .env manquant!"
    echo "Copiez .env.example vers .env et configurez-le"
    exit 1
fi

# Vérifier DEBUG_MODE
if grep -q "DEBUG_MODE=true" .env; then
    echo "⚠️ WARNING: DEBUG_MODE est encore à true!"
    echo "Changez DEBUG_MODE=false dans .env"
fi

echo ""
echo "✅ Déploiement terminé!"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Configurez votre .env avec les bonnes valeurs"
echo "2. Importez votre base de données MySQL"
echo "3. Configurez Apache/Nginx"
echo "4. Testez votre site!"
