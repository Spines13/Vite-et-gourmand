-- =====================================================================
-- Vite & Gourmand - Jeu de donnees initial
-- A executer apres schema.sql
-- =====================================================================
USE vite_et_gourmand;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------
INSERT INTO role (role_id, code, libelle) VALUES
    (1, 'utilisateur', 'Utilisateur'),
    (2, 'employe', 'Employe'),
    (3, 'administrateur', 'Administrateur');

-- ---------------------------------------------------------------------
-- Comptes de demonstration
-- Mots de passe en clair (a communiquer dans le manuel d'utilisation) :
--   admin@vite-et-gourmand.fr    -> AdminVG#2026
--   employe.demo@vite-et-gourmand.fr -> EmployeVG#2026
--   client.demo@vite-et-gourmand.fr  -> ClientVG#2026
-- L'administrateur ne peut etre cree que par ce script (jamais via l'app).
-- ---------------------------------------------------------------------
INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, ville, code_postal, pays, role_id, actif) VALUES
    ('admin@vite-et-gourmand.fr', '$2y$10$D4UuEGErpZhSkpYS6oHKKeeVMFq0DhXL5H46IQ46DmlMZWo8simee', 'Dupont', 'Julie', '0600000001', '12 rue des Chartrons', 'Bordeaux', '33000', 'France', 3, 1),
    ('employe.demo@vite-et-gourmand.fr', '$2y$10$lrBbPg5qLU5cDIsHA1Be8eIlifia/lhODzB6uZ1wDGQrw6vHWXNj2', 'Martin', 'Paul', '0600000002', '5 cours de l''Intendance', 'Bordeaux', '33000', 'France', 2, 1),
    ('client.demo@vite-et-gourmand.fr', '$2y$10$/fzvdFxfs.4xS3yP9Iyvs.nE6Pa1CVOYGm4RcGLX2NbG.Mf6CN1Z2', 'Durand', 'Camille', '0600000003', '8 rue Sainte-Catherine', 'Bordeaux', '33000', 'France', 1, 1);

-- ---------------------------------------------------------------------
-- Themes / regimes / allergenes
-- ---------------------------------------------------------------------
INSERT INTO theme (libelle) VALUES ('Noel'), ('Paques'), ('Classique'), ('Evenement');

INSERT INTO regime (libelle) VALUES ('Classique'), ('Vegetarien'), ('Vegan');

INSERT INTO allergene (libelle) VALUES
    ('Gluten'), ('Crustaces'), ('Oeufs'), ('Poisson'), ('Arachides'),
    ('Soja'), ('Lait'), ('Fruits a coque'), ('Celeri'), ('Moutarde'),
    ('Graines de sesame'), ('Sulfites'), ('Lupin'), ('Mollusques');

-- ---------------------------------------------------------------------
-- Statuts de commande (workflow)
-- ---------------------------------------------------------------------
INSERT INTO statut_commande (code, libelle, ordre) VALUES
    ('en_attente', 'En attente', 1),
    ('accepte', 'Acceptee', 2),
    ('en_preparation', 'En preparation', 3),
    ('en_cours_livraison', 'En cours de livraison', 4),
    ('livre', 'Livree', 5),
    ('en_attente_retour_materiel', 'En attente du retour de materiel', 6),
    ('terminee', 'Terminee', 7),
    ('annulee', 'Annulee', NULL);

-- ---------------------------------------------------------------------
-- Horaires (du lundi au dimanche)
-- ---------------------------------------------------------------------
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, ferme) VALUES
    ('lundi', '09:00:00', '18:00:00', 0),
    ('mardi', '09:00:00', '18:00:00', 0),
    ('mercredi', '09:00:00', '18:00:00', 0),
    ('jeudi', '09:00:00', '18:00:00', 0),
    ('vendredi', '09:00:00', '19:00:00', 0),
    ('samedi', '10:00:00', '17:00:00', 0),
    ('dimanche', NULL, NULL, 1);

-- ---------------------------------------------------------------------
-- Plats
-- ---------------------------------------------------------------------
INSERT INTO plat (plat_id, titre_plat, categorie, photo) VALUES
    (1, 'Velouté de châtaignes', 'entree', NULL),
    (2, 'Foie gras maison et pain d''épices', 'entree', NULL),
    (3, 'Salade printanière aux légumes primeurs', 'entree', NULL),
    (4, 'Chapon rôti aux marrons', 'plat', NULL),
    (5, 'Agneau confit aux herbes de Provence', 'plat', NULL),
    (6, 'Risotto aux asperges vertes', 'plat', NULL),
    (7, 'Bûche de Noël chocolat-marron', 'dessert', NULL),
    (8, 'Tarte au citron meringuée', 'dessert', NULL),
    (9, 'Nid de Pâques chocolat-praliné', 'dessert', NULL);

INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
    (2, 1), -- foie gras / gluten (pain d'épices)
    (7, 1), (7, 3), (7, 7), -- bûche / gluten, oeufs, lait
    (8, 1), (8, 3), (8, 7); -- tarte citron / gluten, oeufs, lait

-- ---------------------------------------------------------------------
-- Menus de demonstration
-- ---------------------------------------------------------------------
INSERT INTO menu (menu_id, titre, description, theme_id, nombre_personne_minimum, prix_personne_minimum, conditions, stock_disponible) VALUES
    (1, 'Menu de Noël Traditionnel', 'Un menu chaleureux et gourmand pour célébrer Noël en famille.', 1, 6, 180.00, 'Commande à passer au minimum 7 jours avant la date de prestation. Conservation au réfrigérateur recommandée.', 15),
    (2, 'Menu de Pâques Végétarien', 'Un menu de saison, frais et entièrement végétarien.', 2, 4, 110.00, 'Commande à passer au minimum 5 jours avant la date de prestation.', 10);

INSERT INTO menu_regime (menu_id, regime_id) VALUES
    (1, 1),
    (2, 2);

INSERT INTO menu_plat (menu_id, plat_id) VALUES
    (1, 1), (1, 2), (1, 4), (1, 5), (1, 7),
    (2, 3), (2, 6), (2, 8), (2, 9);

INSERT INTO menu_image (menu_id, chemin_image, ordre) VALUES
    (1, 'assets/img/menus/Menu_Noel_Traditionnel.jpg', 0),
    (2, 'assets/img/menus/Menu_Paques_Vegetarien.jpg', 0);

-- ---------------------------------------------------------------------
-- Commandes de demonstration (client.demo@vite-et-gourmand.fr)
-- Commande 1 : terminee, 11 personnes (>= 6+5) => reduction 10%, Bordeaux => pas de frais.
--   30 EUR/pers x 11 = 330 ; -10% = 297 ; + 0 frais = 297.00
-- Commande 2 : en preparation, 4 personnes (= minimum, pas de reduction), livraison a Merignac (5 km).
--   27.50 EUR/pers x 4 = 110 ; frais = 5 + 0.59*5 = 7.95 ; total = 117.95
-- ---------------------------------------------------------------------
INSERT INTO commande (commande_id, numero_commande, utilisateur_id, menu_id, statut_id, date_commande, date_prestation, heure_livraison, adresse_livraison, ville_livraison, code_postal_livraison, distance_km, frais_livraison, nombre_personnes, prix_menu_unitaire, reduction_pourcentage, prix_total, pret_materiel, materiel_restitue) VALUES
    (1, 'CMD-2026-0001', 3, 1, 7, '2026-06-01 10:15:00', '2026-06-20', '12:00:00', '8 rue Sainte-Catherine', 'Bordeaux', '33000', 0.00, 0.00, 11, 30.00, 10.00, 297.00, 0, NULL),
    (2, 'CMD-2026-0002', 3, 2, 3, '2026-07-15 09:30:00', '2026-07-25', '19:00:00', '20 avenue du Marechal Leclerc', 'Merignac', '33700', 5.00, 7.95, 4, 27.50, 0.00, 117.95, 0, NULL);

INSERT INTO commande_historique (commande_id, statut_id, date_heure, commentaire) VALUES
    (1, 1, '2026-06-01 10:15:00', NULL),
    (1, 2, '2026-06-01 14:00:00', NULL),
    (1, 3, '2026-06-18 08:00:00', NULL),
    (1, 4, '2026-06-20 09:30:00', NULL),
    (1, 5, '2026-06-20 12:10:00', NULL),
    (1, 7, '2026-06-20 12:10:00', NULL),
    (2, 1, '2026-07-15 09:30:00', NULL),
    (2, 2, '2026-07-15 11:00:00', NULL),
    (2, 3, '2026-07-24 08:00:00', NULL);

INSERT INTO avis (avis_id, commande_id, utilisateur_id, note, commentaire, statut, date_creation, date_moderation) VALUES
    (1, 1, 3, 5, 'Un repas de Noël exceptionnel, l''équipe a été très professionnelle. Merci !', 'valide', '2026-06-21 09:00:00', '2026-06-21 15:00:00');

SET FOREIGN_KEY_CHECKS = 1;
