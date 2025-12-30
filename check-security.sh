#!/bin/bash

# ============================================
# SCRIPT DE VÉRIFICATION PRÉ-DÉPLOIEMENT
# ============================================
# À exécuter AVANT de build l'image Docker
# Vérifie qu'aucune donnée sensible n'est présente

echo "🔍 === VÉRIFICATION DE SÉCURITÉ PRÉ-DÉPLOIEMENT ==="
echo ""

ERRORS=0
WARNINGS=0

# ============================================
# 1. Vérifier les fichiers sensibles
# ============================================
echo "📋 1. Recherche de fichiers sensibles..."

if [ -f ".env" ]; then
    echo "   ⚠️  WARNING: Fichier .env trouvé (doit être dans .dockerignore)"
    WARNINGS=$((WARNINGS+1))
fi

if [ -f ".env.local" ]; then
    echo "   ❌ ERROR: Fichier .env.local trouvé (à supprimer)"
    ERRORS=$((ERRORS+1))
fi

SQL_FILES=$(find . -name "*.sql" -not -path "./vendor/*" -not -path "./docs/merise/MPD.sql")
if [ ! -z "$SQL_FILES" ]; then
    echo "   ❌ ERROR: Fichiers SQL trouvés (dumps de BDD):"
    echo "$SQL_FILES" | sed 's/^/      /'
    ERRORS=$((ERRORS+1))
fi

LOG_FILES=$(find . -name "*.log" -not -path "./vendor/*")
if [ ! -z "$LOG_FILES" ]; then
    echo "   ⚠️  WARNING: Fichiers logs trouvés:"
    echo "$LOG_FILES" | sed 's/^/      /'
    WARNINGS=$((WARNINGS+1))
fi

# ============================================
# 2. Vérifier .dockerignore
# ============================================
echo ""
echo "📋 2. Vérification du fichier .dockerignore..."

if [ ! -f ".dockerignore" ]; then
    echo "   ❌ ERROR: Fichier .dockerignore manquant"
    ERRORS=$((ERRORS+1))
else
    # Vérifier que les entrées critiques sont présentes
    CRITICAL_ENTRIES=(".env" "*.sql" "*.log" "logs/" "tests/")
    for entry in "${CRITICAL_ENTRIES[@]}"; do
        if ! grep -q "^${entry}$" .dockerignore; then
            echo "   ⚠️  WARNING: '$entry' absent de .dockerignore"
            WARNINGS=$((WARNINGS+1))
        fi
    done
    echo "   ✅ .dockerignore existe et semble correct"
fi

# ============================================
# 3. Vérifier .env.example
# ============================================
echo ""
echo "📋 3. Vérification du fichier .env.example..."

if [ ! -f ".env.example" ]; then
    echo "   ⚠️  WARNING: Fichier .env.example manquant"
    WARNINGS=$((WARNINGS+1))
else
    # Vérifier qu'il ne contient pas de vraies valeurs
    if grep -q "root" .env.example || grep -q "admin123" .env.example; then
        echo "   ⚠️  WARNING: .env.example semble contenir des valeurs réelles"
        WARNINGS=$((WARNINGS+1))
    else
        echo "   ✅ .env.example existe et semble safe"
    fi
fi

# ============================================
# 4. Vérifier le Dockerfile
# ============================================
echo ""
echo "📋 4. Vérification du Dockerfile..."

if [ ! -f "Dockerfile" ]; then
    echo "   ❌ ERROR: Dockerfile manquant"
    ERRORS=$((ERRORS+1))
else
    # Vérifier --no-dev pour composer
    if grep -q "composer install --no-dev" Dockerfile; then
        echo "   ✅ Composer avec --no-dev (production)"
    else
        echo "   ⚠️  WARNING: Composer devrait utiliser --no-dev"
        WARNINGS=$((WARNINGS+1))
    fi
    
    # Vérifier qu'il n'y a pas de COPY .env
    if grep -q "COPY \.env" Dockerfile; then
        echo "   ❌ ERROR: Le Dockerfile copie le fichier .env !"
        ERRORS=$((ERRORS+1))
    else
        echo "   ✅ Pas de COPY .env dans le Dockerfile"
    fi
fi

# ============================================
# 5. Vérifier les uploads
# ============================================
echo ""
echo "📋 5. Vérification des dossiers uploads..."

UPLOAD_SIZE=$(du -sh public/uploads 2>/dev/null | cut -f1)
if [ ! -z "$UPLOAD_SIZE" ]; then
    echo "   ℹ️  Taille du dossier uploads: $UPLOAD_SIZE"
    
    # Compter les fichiers
    FILE_COUNT=$(find public/uploads -type f | wc -l)
    if [ $FILE_COUNT -gt 0 ]; then
        echo "   ⚠️  WARNING: $FILE_COUNT fichiers dans uploads/ (seront exclus par .dockerignore)"
        WARNINGS=$((WARNINGS+1))
    fi
else
    echo "   ✅ Dossier uploads vide ou inexistant"
fi

# ============================================
# 6. Vérifier les variables d'environnement dans le code
# ============================================
echo ""
echo "📋 6. Vérification des variables d'environnement..."

# Chercher des valeurs hardcodées (passwords, secrets)
HARDCODED=$(grep -r "password\s*=\s*['\"]" --include="*.php" src/ config/ 2>/dev/null | grep -v "getenv" | grep -v "_ENV")
if [ ! -z "$HARDCODED" ]; then
    echo "   ⚠️  WARNING: Mots de passe potentiellement hardcodés trouvés:"
    echo "$HARDCODED" | sed 's/^/      /'
    WARNINGS=$((WARNINGS+1))
else
    echo "   ✅ Pas de credentials hardcodés détectés"
fi

# ============================================
# 7. Vérifier la configuration Docker Compose
# ============================================
echo ""
echo "📋 7. Vérification du docker-compose.yml..."

if [ ! -f "docker-compose.yml" ]; then
    echo "   ❌ ERROR: docker-compose.yml manquant"
    ERRORS=$((ERRORS+1))
else
    # Vérifier qu'il n'y a pas de mots de passe en clair
    if grep -q "MYSQL_ROOT_PASSWORD:" docker-compose.yml; then
        PASSWORD_VALUE=$(grep "MYSQL_ROOT_PASSWORD:" docker-compose.yml | cut -d':' -f2 | xargs)
        if [ "$PASSWORD_VALUE" != "\${DB_PASSWORD}" ] && [ "$PASSWORD_VALUE" != "\${MYSQL_ROOT_PASSWORD}" ]; then
            echo "   ⚠️  WARNING: Mot de passe MySQL hardcodé dans docker-compose.yml"
            WARNINGS=$((WARNINGS+1))
        else
            echo "   ✅ Variables d'environnement utilisées pour les credentials"
        fi
    fi
fi

# ============================================
# RÉSUMÉ
# ============================================
echo ""
echo "============================================"
echo "📊 RÉSUMÉ DE LA VÉRIFICATION"
echo "============================================"
echo "❌ Erreurs critiques : $ERRORS"
echo "⚠️  Avertissements    : $WARNINGS"
echo ""

if [ $ERRORS -gt 0 ]; then
    echo "🚨 DÉPLOIEMENT BLOQUÉ : Corriger les erreurs avant de continuer !"
    exit 1
elif [ $WARNINGS -gt 0 ]; then
    echo "⚠️  ATTENTION : Vérifier les avertissements avant de déployer"
    echo ""
    read -p "Continuer quand même ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Déploiement annulé"
        exit 1
    fi
fi

echo "✅ Vérifications passées ! Vous pouvez build l'image Docker."
echo ""
echo "Prochaines étapes :"
echo "  1. docker-compose build --no-cache"
echo "  2. docker-compose up -d"
echo "  3. Configurer le .env sur le serveur"
echo ""
exit 0
