## 🚀 Démo live

Le projet est accessible en ligne à l’adresse suivante :

👉 https://portfolio.berancan-guven.fr

# Portfolio V3 - Berancan Guven

Portfolio professionnel développé en PHP natif avec architecture **MVC + Repository Pattern**, mettant en avant mes compétences en développement web full-stack.

## 🚀 Technologies utilisées

- **Backend**: PHP 8.1+ (POO, MVC)
- **Frontend**: HTML5, CSS3 (SCSS/Sass), JavaScript Vanilla
- **Base de données**: MySQL 8.0
- **Sécurité**: Authentification 2FA (TOTP), Protection CSRF, Sessions sécurisées
- **Email**: PHPMailer avec SMTP
- **Gestion de configuration**: Variables d'environnement via Dotenv

## 📁 Architecture du projet

```
PortfolioV3/
├── config/                 # Configuration de l'application
│   ├── database.php       # Connexion PDO à la base de données
│   └── routes.php         # Définition des routes
│
├── public/                # Point d'entrée public (DocumentRoot)
│   ├── index.php         # Front controller
│   ├── css/              # Fichiers CSS compilés
│   ├── js/               # Scripts JavaScript
│   └── img/              # Images et assets
│
├── src/                   # Code source de l'application
│   ├── Controllers/      # Contrôleurs MVC
│   ├── Repositories/     # Couche d'accès aux données (Repository Pattern)
│   ├── Core/             # Classes core (Router, Database)
│   ├── Middleware/       # Middlewares (Auth, CSRF)
│   ├── Views/            # Templates PHP
│   └── helpers.php       # Fonctions utilitaires
│
├── scss/                  # Fichiers sources SCSS
│   ├── base/             # Reset, variables, mixins
│   ├── components/       # Composants réutilisables
│   ├── pages/            # Styles spécifiques par page
│   └── style.scss        # Point d'entrée SCSS
│
├── tests/                 # Tests unitaires PHPUnit
├── vendor/                # Dépendances Composer
├── bootstrap.php         # Initialisation de l'application
└── composer.json         # Dépendances PHP
```

## ⚙️ Installation

### Prérequis

- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Sass (pour la compilation CSS)

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone https://github.com/BRCorg/PortfolioV3.git
cd PortfolioV3
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer l'environnement**

Copier le fichier `.env.example` en `.env` et configurer selon votre environnement :
```bash
cp .env.example .env
```

4. **Créer la base de données**
```sql
CREATE DATABASE portfolio_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importer le schéma de la base de données (structure fournie séparément).

5. **Compiler les fichiers SCSS**
```bash
sass scss/style.scss public/css/style.css --style=compressed --no-source-map
```

Pour le mode watch en développement :
```bash
sass --watch scss:public/css --style=expanded --no-source-map
```

6. **Configurer le serveur web**

**Apache** : Le `.htaccess` est déjà configuré pour rediriger vers `public/`

**Nginx** : Configuration exemple
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /path/to/PortfolioV3/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🏗️ Architecture & Design Patterns

### Repository Pattern
- **Séparation des responsabilités** : Controllers → Repositories → Database
- **Abstraction de la couche de persistance** : Facilite les tests et la maintenance
- **7 Repositories** : Project, Skill, Category, Contact, User + BaseRepository + Interface

### Singleton Pattern (Database)
- **Une seule instance** de connexion PDO pour toute l'application
- **Optimisation des ressources** : Évite les connexions multiples
- **Thread-safe** : Protection contre le clonage et la désérialisation

### Avantages de cette architecture
- ✅ Code DRY (Don't Repeat Yourself) - Aucune duplication
- ✅ SOLID Principles respectés
- ✅ Facilement testable (mockable)
- ✅ Scalable et maintenable

## 🔒 Sécurité

- **Authentification 2FA** : TOTP (Google Authenticator) avec codes de backup
- **Protection CSRF** : Tokens uniques pour chaque formulaire
- **Rate Limiting** : Protection contre le brute force (IP + Email)
- **Sessions sécurisées** : Configuration hardened avec flags httponly, secure, samesite
- **Headers de sécurité HTTP** : XSS Protection, X-Frame-Options, Content-Type-Options
- **Préparation des requêtes SQL** : Protection contre les injections SQL via PDO
- **Variables d'environnement** : Isolation de la configuration sensible
- **Security Logger** : Traçabilité des événements critiques

## 🧪 Tests

Lancer les tests unitaires :
```bash
./vendor/bin/phpunit
```

## 📝 Fonctionnalités principales

### Partie publique
- Page d'accueil avec présentation
- Portfolio de projets avec galerie d'images
- Liste des compétences techniques
- Formulaire de contact avec envoi d'email
- Mode sombre/clair
- Design responsive (mobile, tablette, desktop)
- Page 404 personnalisée

### Partie administration
- Authentification sécurisée avec 2FA
- Dashboard de gestion
- CRUD de projets
- CRUD de compétences
- Gestion des messages de contact
- Sauvegarde et restauration 2FA

## 🎨 Personnalisation

Les variables de design sont centralisées dans `scss/base/_variables.scss` :
- Couleurs principales
- Espacements
- Tailles de police
- Breakpoints responsive

## 📦 Dépendances principales

- `vlucas/phpdotenv` : Gestion des variables d'environnement
- `phpmailer/phpmailer` : Envoi d'emails
- `spomky-labs/otphp` : Génération TOTP pour 2FA
- `phpunit/phpunit` : Tests unitaires

## 📄 Licence

Ce projet est un portfolio personnel. Tous droits réservés.

## 👤 Auteur

**Berancan Guven**
Développeur Web Full-Stack

- Email: guvenberancan1@gmail.com
- GitHub: [@BRCorg](https://github.com/BRCorg)

---

Développé avec ❤️ en PHP natif
