-- =====================================================================
-- Vite & Gourmand - Schema base de donnees relationnelle (MySQL/MariaDB)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS vite_et_gourmand
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vite_et_gourmand;

-- ---------------------------------------------------------------------
-- Habilitations
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS role;
CREATE TABLE role (
    role_id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,   -- utilisateur | employe | administrateur
    libelle     VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Le role "administrateur" n'est jamais attribuable depuis l'application
-- (cf. cahier des charges) : seule une insertion SQL directe le permet.
DROP TABLE IF EXISTS utilisateur;
CREATE TABLE utilisateur (
    utilisateur_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email                VARCHAR(180) NOT NULL UNIQUE,   -- sert d'identifiant de connexion
    mot_de_passe         VARCHAR(255) NOT NULL,          -- hash password_hash() (bcrypt/argon2), jamais en clair
    nom                  VARCHAR(80)  NOT NULL,
    prenom               VARCHAR(80)  NOT NULL,
    telephone            VARCHAR(20)  NULL,
    adresse_postale      VARCHAR(180) NULL,
    ville                VARCHAR(100) NULL,
    code_postal          VARCHAR(10)  NULL,
    pays                 VARCHAR(80)  NULL DEFAULT 'France',
    role_id              TINYINT UNSIGNED NOT NULL,
    actif                TINYINT(1) NOT NULL DEFAULT 1,  -- permet de desactiver un compte employe (depart entreprise)
    token_reset          VARCHAR(64) NULL,                -- jeton unique envoye par mail pour reinitialisation
    token_reset_expire   DATETIME NULL,
    date_creation        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES role (role_id)
) ENGINE=InnoDB;

CREATE INDEX idx_utilisateur_role ON utilisateur (role_id);

-- ---------------------------------------------------------------------
-- Catalogue (menus / plats)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS theme;
CREATE TABLE theme (
    theme_id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(50) NOT NULL UNIQUE   -- Noel, Paques, classique, evenement, ...
) ENGINE=InnoDB;

DROP TABLE IF EXISTS regime;
CREATE TABLE regime (
    regime_id   TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(50) NOT NULL UNIQUE   -- vegetarien, vegan, classique, ...
) ENGINE=InnoDB;

DROP TABLE IF EXISTS allergene;
CREATE TABLE allergene (
    allergene_id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle      VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plat;
CREATE TABLE plat (
    plat_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre_plat   VARCHAR(120) NOT NULL,
    categorie    ENUM('entree','plat','dessert') NOT NULL,
    photo        VARCHAR(255) NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plat_allergene;
CREATE TABLE plat_allergene (
    plat_id      INT UNSIGNED NOT NULL,
    allergene_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    CONSTRAINT fk_pa_plat      FOREIGN KEY (plat_id)      REFERENCES plat (plat_id)           ON DELETE CASCADE,
    CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS menu;
CREATE TABLE menu (
    menu_id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre                    VARCHAR(150) NOT NULL,
    description              TEXT NOT NULL,
    theme_id                 TINYINT UNSIGNED NOT NULL,
    nombre_personne_minimum  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    prix_personne_minimum    DECIMAL(8,2) NOT NULL,        -- prix total pour le nb de personnes minimum
    conditions               TEXT NULL,                    -- delai de commande, precautions de stockage, etc.
    stock_disponible         SMALLINT NOT NULL DEFAULT 0,   -- nombre de commandes restantes possibles
    actif                    TINYINT(1) NOT NULL DEFAULT 1,
    date_creation            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_theme FOREIGN KEY (theme_id) REFERENCES theme (theme_id)
) ENGINE=InnoDB;

CREATE INDEX idx_menu_theme ON menu (theme_id);

DROP TABLE IF EXISTS menu_image;
CREATE TABLE menu_image (
    image_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id      INT UNSIGNED NOT NULL,
    chemin_image VARCHAR(255) NOT NULL,
    ordre        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_image_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS menu_regime;
CREATE TABLE menu_regime (
    menu_id    INT UNSIGNED NOT NULL,
    regime_id  TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (menu_id, regime_id),
    CONSTRAINT fk_mr_menu   FOREIGN KEY (menu_id)   REFERENCES menu (menu_id)     ON DELETE CASCADE,
    CONSTRAINT fk_mr_regime FOREIGN KEY (regime_id) REFERENCES regime (regime_id) ON DELETE CASCADE
) ENGINE=InnoDB;

DROP TABLE IF EXISTS menu_plat;
CREATE TABLE menu_plat (
    menu_id  INT UNSIGNED NOT NULL,
    plat_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    CONSTRAINT fk_mp_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Horaires (pied de page)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS horaire;
CREATE TABLE horaire (
    horaire_id       TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jour             ENUM('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche') NOT NULL UNIQUE,
    heure_ouverture  TIME NULL,
    heure_fermeture  TIME NULL,
    ferme            TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Commandes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS statut_commande;
CREATE TABLE statut_commande (
    statut_id  TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(30) NOT NULL UNIQUE,
    libelle    VARCHAR(60) NOT NULL,
    ordre      TINYINT UNSIGNED NULL   -- ordre logique du workflow (NULL pour "annulee", hors flux normal)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS commande;
CREATE TABLE commande (
    commande_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_commande         VARCHAR(20) NOT NULL UNIQUE,
    utilisateur_id           INT UNSIGNED NOT NULL,
    menu_id                  INT UNSIGNED NOT NULL,
    statut_id                TINYINT UNSIGNED NOT NULL,
    date_commande            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_prestation          DATE NOT NULL,
    heure_livraison          TIME NOT NULL,
    adresse_livraison        VARCHAR(180) NOT NULL,
    ville_livraison          VARCHAR(100) NOT NULL,
    code_postal_livraison    VARCHAR(10) NOT NULL,
    distance_km               DECIMAL(6,2) NOT NULL DEFAULT 0,   -- 0 si Bordeaux intra-muros
    frais_livraison           DECIMAL(6,2) NOT NULL DEFAULT 0,   -- 5€ + 0,59€/km si hors Bordeaux, 0 sinon
    nombre_personnes          SMALLINT UNSIGNED NOT NULL,
    prix_menu_unitaire        DECIMAL(8,2) NOT NULL,             -- prix_personne_minimum du menu au moment de la commande
    reduction_pourcentage     DECIMAL(4,2) NOT NULL DEFAULT 0,   -- 10.00 si nb_personnes >= nb_min + 5
    prix_total                DECIMAL(8,2) NOT NULL,
    pret_materiel             TINYINT(1) NOT NULL DEFAULT 0,
    materiel_restitue         TINYINT(1) NULL,
    materiel_date_limite      DATETIME NULL,                     -- date_passage_statut "en_attente_retour_materiel" + 10 j ouvres
    motif_annulation          TEXT NULL,
    mode_contact_annulation   VARCHAR(30) NULL,                  -- "telephone" | "mail"
    date_creation             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id),
    CONSTRAINT fk_commande_menu        FOREIGN KEY (menu_id)        REFERENCES menu (menu_id),
    CONSTRAINT fk_commande_statut      FOREIGN KEY (statut_id)      REFERENCES statut_commande (statut_id)
) ENGINE=InnoDB;

CREATE INDEX idx_commande_utilisateur ON commande (utilisateur_id);
CREATE INDEX idx_commande_menu        ON commande (menu_id);
CREATE INDEX idx_commande_statut      ON commande (statut_id);

-- Historique des changements de statut (suivi de commande cote utilisateur)
DROP TABLE IF EXISTS commande_historique;
CREATE TABLE commande_historique (
    historique_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id    INT UNSIGNED NOT NULL,
    statut_id      TINYINT UNSIGNED NOT NULL,
    date_heure     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire    VARCHAR(255) NULL,
    CONSTRAINT fk_historique_commande FOREIGN KEY (commande_id) REFERENCES commande (commande_id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_statut   FOREIGN KEY (statut_id)   REFERENCES statut_commande (statut_id)
) ENGINE=InnoDB;

CREATE INDEX idx_historique_commande ON commande_historique (commande_id);

-- ---------------------------------------------------------------------
-- Avis clients (rattaches a une commande terminee)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS avis;
CREATE TABLE avis (
    avis_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id     INT UNSIGNED NOT NULL UNIQUE,   -- un seul avis par commande
    utilisateur_id  INT UNSIGNED NOT NULL,
    note            TINYINT UNSIGNED NOT NULL,
    commentaire     TEXT NULL,
    statut          ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_moderation DATETIME NULL,
    CONSTRAINT fk_avis_commande    FOREIGN KEY (commande_id)    REFERENCES commande (commande_id),
    CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id),
    CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE INDEX idx_avis_statut ON avis (statut);

-- ---------------------------------------------------------------------
-- Contact
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS contact;
CREATE TABLE contact (
    contact_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre         VARCHAR(150) NOT NULL,
    description   TEXT NOT NULL,
    email         VARCHAR(180) NOT NULL,
    date_envoi    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
