# Vite & Gourmand

Application web de présentation et de commande des menus événementiels du
traiteur bordelais Vite & Gourmand.

## Stack technique

- Front : HTML5, CSS, JavaScript (vanilla)
- Back-end : PHP natif + PDO
- Base de données relationnelle : MySQL / MariaDB
- Base de données non relationnelle : MongoDB (reporting des commandes, cf. [`database/mongodb/README.md`](database/mongodb/README.md))

## Prérequis

- [XAMPP](https://www.apachefriends.org/fr/index.html) (Apache + PHP 8.1 ou supérieur + MySQL/MariaDB)
- [MongoDB Community Server](https://www.mongodb.com/try/download/community) (binaires `mongod`)
- [Composer](https://getcomposer.org/) (installe la librairie PHP `mongodb/mongodb`)
- L'extension PHP `mongodb` ([DLL Windows](https://pecl.php.net/package/mongodb)), à placer dans
  `php/ext/` et activer avec `extension=mongodb` dans `php.ini` (choisir la version TS/NTS et
  x64/x86 correspondant à votre PHP, visible via `php -i`)
- Git

## Installation en local

1. **Cloner le dépôt** dans le dossier `htdocs` de XAMPP :
   ```
   cd C:\xampp\htdocs
   git clone <url-du-depot> Vite_Et_Gourmand
   ```

2. **Configurer l'environnement** : dupliquer `.env.example` en `.env` et
   ajuster si besoin les identifiants de connexion (les valeurs par défaut
   correspondent à une installation XAMPP standard, root sans mot de passe).
   ```
   copy .env.example .env
   ```
   Installer ensuite la dépendance PHP MongoDB :
   ```
   php composer.phar install
   ```

3. **Créer la base de données relationnelle** : démarrer MySQL depuis le
   panneau de contrôle XAMPP, puis importer les scripts dans l'ordre. Le
   flag `--default-character-set=utf8mb4` est indispensable : sans lui, le
   client `mysql.exe` utilise l'encodage par défaut de Windows et corrompt
   les caractères accentués (Noël, Pâques...) dès l'import.
   ```
   C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root < database\schema.sql
   C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root < database\seed.sql
   ```

4. **Initialiser la base non relationnelle** : démarrer `mongod` (par exemple
   `mongod.exe --dbpath C:\mongodb\data --logpath C:\mongodb\logs\mongod.log`),
   puis initialiser la collection :
   ```
   mongosh < database\mongodb\init.js
   ```
   Si `mongosh` n'est pas installé (le zip des binaires MongoDB ne l'inclut
   pas toujours), l'équivalent PHP suivant fonctionne aussi bien :
   ```
   php database\mongodb\init.php
   ```

5. **Servir l'application** : le répertoire web public est `public/`
   (le reste du code n'est volontairement pas accessible directement en
   HTTP). Deux options :
   - **Accès rapide sans configuration** : démarrer Apache depuis XAMPP puis
     ouvrir `http://localhost/Vite_Et_Gourmand/public/`
   - **VirtualHost (recommandé)** : dans
     `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, ajouter :
     ```apache
     <VirtualHost *:80>
         ServerName vite-et-gourmand.local
         DocumentRoot "C:/xampp/htdocs/Vite_Et_Gourmand/public"
         <Directory "C:/xampp/htdocs/Vite_Et_Gourmand/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
     puis ajouter `127.0.0.1 vite-et-gourmand.local` dans
     `C:\Windows\System32\drivers\etc\hosts`, et redémarrer Apache. L'application
     est alors accessible sur `http://vite-et-gourmand.local/`.

## Emails (local)

L'envoi d'email utilise la fonction native `mail()` de PHP, configurée dans
`php.ini` (`SMTP=localhost`, `smtp_port=25`) pour passer par **Mercury Mail**,
le serveur SMTP inclus dans XAMPP. Démarrer Mercury Mail depuis le panneau de
contrôle XAMPP pour tester localement les emails (bienvenue, confirmation de
commande, changement de statut, réinitialisation de mot de passe...).

## Documentation

Le dossier [`docs/`](docs) contient les livrables documentaires, chacun en
version source (`.html`) et export PDF prêt à l'emploi :

- `manuel-utilisateur.pdf` — présentation de l'application et parcours par profil
- `charte-graphique.pdf` — palette de couleurs, typographie et maquettes desktop/mobile
- `doc-technique.pdf` — choix technologiques, MCD, diagrammes UML, déploiement
- `doc-gestion-projet.pdf` — méthodologie et backlog du projet

Pour régénérer un PDF après modification du `.html` correspondant :
```
"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --headless --disable-gpu --print-to-pdf="docs\<nom>.pdf" --no-pdf-header-footer "file:///C:/xampp/htdocs/Vite_Et_Gourmand/docs/<nom>.html"
```

## Comptes de démonstration

| Rôle           | Email                              | Mot de passe    |
|----------------|-------------------------------------|-----------------|
| Administrateur | admin@vite-et-gourmand.fr           | AdminVG#2026    |
| Employé        | employe.demo@vite-et-gourmand.fr    | EmployeVG#2026  |
| Utilisateur    | client.demo@vite-et-gourmand.fr     | ClientVG#2026   |

Le compte administrateur n'est jamais créable depuis l'application : il
n'existe que via `database/seed.sql`, conformément au cahier des charges.

## Structure du projet

```
public/            Racine web (front controller, assets, espaces utilisateur/employé/admin)
src/Config/         Connexion PDO, connexion Mongo, bootstrap (session, .env)
src/Models/         Accès aux données (PDO, requêtes préparées)
src/Services/       Règles métier (calcul de prix, envoi de mails, statistiques)
src/Views/partials/ Gabarits communs (en-tête, pied de page, navigation)
database/           Scripts SQL (schéma + jeu de données) et conception MongoDB
docs/               Documentation livrable (technique, projet, manuel, charte graphique)
```

## Organisation Git

- `main` : version stable, déployée en production
- `developpement` : intégration des fonctionnalités validées
- `feature/<nom-fonctionnalite>` : une branche par fonctionnalité, créée
  depuis `developpement`, testée puis fusionnée dans `developpement` ; une
  fois `developpement` validée, elle est fusionnée dans `main`.
