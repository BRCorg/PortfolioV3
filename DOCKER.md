# Déploiement Docker - Portfolio V3

Guide complet pour déployer le portfolio sur un VPS avec Docker.

---

## 📦 Qu'est-ce que Docker ?

Docker permet d'empaqueter votre application avec toutes ses dépendances dans un conteneur isolé, garantissant qu'elle fonctionnera de la même manière partout.

**Avantages :**
- ✅ Isolation complète de l'application
- ✅ Déploiement rapide et reproductible
- ✅ Gestion simplifiée des dépendances
- ✅ Facile à mettre à jour et à rollback

---

## 🚀 Déploiement sur VPS

### Prérequis sur le VPS

```bash
# Se connecter au VPS
ssh user@votre-vps.com

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Installer Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Vérifier l'installation
docker --version
docker-compose --version
```

---

### Étape 1 : Cloner le projet sur le VPS

```bash
# Cloner depuis GitHub
git clone https://github.com/BRCorg/PortfolioV3.git
cd PortfolioV3
```

---

### Étape 2 : Configurer l'environnement

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer le fichier .env
nano .env
```

**Configuration .env pour production :**

```env
# Base de données (utilisez des valeurs sécurisées !)
DB_HOST=db
DB_PORT=3306
DB_NAME=portfolio_v2
DB_USER=portfolio_user
DB_PASSWORD=CHANGEZ_CE_MOT_DE_PASSE_FORT

# MySQL root password (pour docker-compose)
DB_ROOT_PASSWORD=CHANGEZ_CE_MOT_DE_PASSE_ROOT

# Administration
ADMIN_SECRET_URL=/votre-url-admin-ultra-secrete
SESSION_SECRET=generer_une_cle_aleatoire_longue_ici

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre@email.com
MAIL_PASSWORD=votre_mot_de_passe_app
ADMIN_EMAIL=admin@example.com

# Application
DEBUG_MODE=false
APP_ENV=production
```

**Générer des mots de passe sécurisés :**
```bash
# Générer un mot de passe aléatoire
openssl rand -base64 32
```

---

### Étape 3 : Compiler les assets CSS

**Sur votre machine locale (avant de push) :**

```bash
# Compiler SCSS en production
sass scss/style.scss public/css/style.css --style=compressed --no-source-map
```

Ou **sur le VPS si Sass est installé :**

```bash
# Installer Sass
npm install -g sass

# Compiler
sass scss/style.scss public/css/style.css --style=compressed --no-source-map
```

---

### Étape 4 : Construire et démarrer les conteneurs

```bash
# Construire l'image Docker
docker-compose build

# Démarrer les services en arrière-plan
docker-compose up -d

# Vérifier que tout fonctionne
docker-compose ps
```

Vous devriez voir 3 conteneurs actifs :
- `portfolio_app` (PHP + Nginx)
- `portfolio_db` (MySQL)
- `portfolio_phpmyadmin` (optionnel)

---

### Étape 5 : Importer la base de données

**Option A : Via phpMyAdmin**

Accédez à `http://votre-vps-ip:8080` et importez votre fichier SQL.

**Option B : En ligne de commande**

```bash
# Copier le fichier SQL dans le conteneur
docker cp votre_dump.sql portfolio_db:/tmp/dump.sql

# Importer dans MySQL
docker exec -i portfolio_db mysql -u portfolio_user -p portfolio_v2 < /tmp/dump.sql
```

---

### Étape 6 : Vérifier le déploiement

```bash
# Voir les logs en temps réel
docker-compose logs -f app

# Tester l'application
curl http://localhost
```

Votre site devrait être accessible sur `http://votre-vps-ip`

---

## 🔧 Commandes utiles

### Gestion des conteneurs

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Redémarrer les services
docker-compose restart

# Voir les logs
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f app
docker-compose logs -f db
```

### Mise à jour de l'application

```bash
# Pull les dernières modifications
git pull origin main

# Rebuild et redémarrer
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Accès aux conteneurs

```bash
# Accéder au shell du conteneur app
docker exec -it portfolio_app sh

# Accéder à MySQL
docker exec -it portfolio_db mysql -u root -p
```

### Sauvegarde de la base de données

```bash
# Exporter la base de données
docker exec portfolio_db mysqldump -u portfolio_user -p portfolio_v2 > backup_$(date +%Y%m%d).sql
```

---

## 🌐 Configuration avec un nom de domaine

### Avec Nginx Reverse Proxy (recommandé)

Si vous avez d'autres sites sur le même VPS, utilisez un reverse proxy :

```bash
# Installer nginx sur le VPS (pas dans Docker)
sudo apt install nginx

# Créer la configuration du site
sudo nano /etc/nginx/sites-available/portfolio
```

**Configuration Nginx :**

```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Activer le site
sudo ln -s /etc/nginx/sites-available/portfolio /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Avec SSL/HTTPS (Certbot)

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-nginx

# Obtenir un certificat SSL gratuit
sudo certbot --nginx -d votre-domaine.com -d www.votre-domaine.com

# Renouvellement automatique (déjà configuré par Certbot)
sudo certbot renew --dry-run
```

---

## 🔒 Sécurité

### Firewall (UFW)

```bash
# Installer UFW
sudo apt install ufw

# Autoriser SSH
sudo ufw allow 22/tcp

# Autoriser HTTP et HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Activer le firewall
sudo ufw enable
```

### Permissions

```bash
# S'assurer que .env est protégé
chmod 600 .env

# Uploads en écriture
chmod 775 public/uploads
```

---

## 📊 Monitoring

### Voir l'utilisation des ressources

```bash
# Statistiques des conteneurs
docker stats

# Espace disque utilisé par Docker
docker system df
```

### Nettoyage

```bash
# Supprimer les images non utilisées
docker image prune -a

# Nettoyer tout (containers, images, volumes non utilisés)
docker system prune -a --volumes
```

---

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Voir les logs d'erreur
docker-compose logs

# Vérifier la configuration
docker-compose config
```

### Erreur de connexion à la base de données

```bash
# Vérifier que le conteneur MySQL est actif
docker-compose ps db

# Tester la connexion
docker exec -it portfolio_app ping db
```

### Le site affiche une erreur 500

```bash
# Vérifier les logs PHP
docker-compose logs app

# Vérifier les permissions
docker exec -it portfolio_app ls -la /var/www/html
```

---

## 📁 Structure Docker

```
PortfolioV2/
├── Dockerfile              # Image principale (PHP + Nginx)
├── docker-compose.yml      # Orchestration des services
├── .dockerignore          # Fichiers exclus de l'image
└── docker/
    ├── nginx/
    │   ├── nginx.conf     # Config Nginx globale
    │   └── default.conf   # Config du site
    ├── supervisor/
    │   └── supervisord.conf  # Gestion des processus
    └── mysql/
        └── init.sql       # Script d'initialisation DB
```

---

## ✅ Checklist de déploiement

- [ ] Docker et Docker Compose installés sur le VPS
- [ ] Projet cloné depuis GitHub
- [ ] Fichier `.env` configuré avec valeurs de production
- [ ] Mots de passe sécurisés générés
- [ ] CSS compilé en mode production
- [ ] Conteneurs construits et démarrés
- [ ] Base de données importée
- [ ] Site accessible via IP ou domaine
- [ ] SSL/HTTPS configuré (si domaine)
- [ ] Firewall activé
- [ ] Sauvegarde automatique configurée

---

**Déploiement Docker réussi ! 🎉**
