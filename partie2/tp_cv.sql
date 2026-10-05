-- =====================================================================
--  TP1 - Partie 2 : Base de données pour la génération de CV
--  Identifiant de l'utilisateur : l'adresse email (clé primaire)
-- =====================================================================

-- Les textes de ce script (accents des ENUM) sont en UTF-8
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS tp_cv
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tp_cv;

-- On supprime les anciennes tables si on réimporte le script
-- (d'abord les tables qui dépendent de etudiant, puis etudiant)
DROP TABLE IF EXISTS centre_interet, langue, competence, stage, formation;
DROP TABLE IF EXISTS etudiant;

-- ---------------------------------------------------------------------
-- 1) Table principale : etudiant
--    Nom + coordonnées (email, téléphone, adresse) + photo
-- ---------------------------------------------------------------------
CREATE TABLE etudiant (
    email          VARCHAR(100)  NOT NULL,
    nom            VARCHAR(50)   NOT NULL,
    prenom         VARCHAR(50)   NOT NULL,
    telephone      VARCHAR(20)   NOT NULL,
    adresse        VARCHAR(255)  NOT NULL,
    photo          VARCHAR(255)  NULL,          -- chemin du fichier, ex : uploads/photos/ali.jpg
    date_creation  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (email),
    CONSTRAINT chk_email CHECK (email LIKE '%_@_%._%')
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 2) Formations (diplômes, cursus)
-- ---------------------------------------------------------------------
CREATE TABLE formation (
    id_formation   INT           NOT NULL AUTO_INCREMENT,
    email          VARCHAR(100)  NOT NULL,
    diplome        VARCHAR(100)  NOT NULL,      -- ex : Cycle ingénieur GI
    etablissement  VARCHAR(100)  NOT NULL,      -- ex : ENSA Tétouan
    date_debut     DATE          NOT NULL,
    date_fin       DATE          NULL,          -- NULL = en cours
    description    TEXT          NULL,

    PRIMARY KEY (id_formation),
    FOREIGN KEY (email) REFERENCES etudiant(email)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3) Stages
-- ---------------------------------------------------------------------
CREATE TABLE stage (
    id_stage       INT           NOT NULL AUTO_INCREMENT,
    email          VARCHAR(100)  NOT NULL,
    intitule       VARCHAR(100)  NOT NULL,      -- ex : Stage de développement web
    entreprise     VARCHAR(100)  NOT NULL,
    lieu           VARCHAR(100)  NULL,
    date_debut     DATE          NOT NULL,
    date_fin       DATE          NULL,
    description    TEXT          NULL,

    PRIMARY KEY (id_stage),
    FOREIGN KEY (email) REFERENCES etudiant(email)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4) Compétences
-- ---------------------------------------------------------------------
CREATE TABLE competence (
    id_competence  INT           NOT NULL AUTO_INCREMENT,
    email          VARCHAR(100)  NOT NULL,
    libelle        VARCHAR(100)  NOT NULL,      -- ex : PHP, MySQL, Java
    niveau         ENUM('Débutant', 'Intermédiaire', 'Avancé', 'Expert') NULL,

    PRIMARY KEY (id_competence),
    FOREIGN KEY (email) REFERENCES etudiant(email)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 5) Langues
-- ---------------------------------------------------------------------
CREATE TABLE langue (
    id_langue      INT           NOT NULL AUTO_INCREMENT,
    email          VARCHAR(100)  NOT NULL,
    libelle        VARCHAR(50)   NOT NULL,      -- ex : Français, Anglais
    niveau         ENUM('Débutant', 'Intermédiaire', 'Courant', 'Bilingue', 'Langue maternelle') NOT NULL,

    PRIMARY KEY (id_langue),
    FOREIGN KEY (email) REFERENCES etudiant(email)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 6) Centres d'intérêt
-- ---------------------------------------------------------------------
CREATE TABLE centre_interet (
    id_interet     INT           NOT NULL AUTO_INCREMENT,
    email          VARCHAR(100)  NOT NULL,
    libelle        VARCHAR(100)  NOT NULL,      -- ex : Football, Lecture

    PRIMARY KEY (id_interet),
    FOREIGN KEY (email) REFERENCES etudiant(email)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;


-- =====================================================================
--  Jeu de données d'exemple (pour tester la génération du CV)
-- =====================================================================
INSERT INTO etudiant (email, nom, prenom, telephone, adresse, photo) VALUES
('ali.benali@gmail.com', 'Benali', 'Ali', '0612345678', '12 rue Mohammed V, Tétouan', NULL);

INSERT INTO formation (email, diplome, etablissement, date_debut, date_fin) VALUES
('ali.benali@gmail.com', 'Cycle ingénieur - Génie Informatique', 'ENSA Tétouan', '2023-09-01', NULL),
('ali.benali@gmail.com', 'Classes préparatoires intégrées (2AP)', 'ENSA Tétouan', '2021-09-01', '2023-06-30');

INSERT INTO stage (email, intitule, entreprise, lieu, date_debut, date_fin, description) VALUES
('ali.benali@gmail.com', 'Stage de développement web', 'Société X', 'Tanger', '2025-07-01', '2025-08-31', 'Développement d''une application PHP/MySQL');

INSERT INTO competence (email, libelle, niveau) VALUES
('ali.benali@gmail.com', 'PHP', 'Avancé'),
('ali.benali@gmail.com', 'MySQL', 'Intermédiaire');

INSERT INTO langue (email, libelle, niveau) VALUES
('ali.benali@gmail.com', 'Arabe', 'Langue maternelle'),
('ali.benali@gmail.com', 'Français', 'Courant'),
('ali.benali@gmail.com', 'Anglais', 'Intermédiaire');

INSERT INTO centre_interet (email, libelle) VALUES
('ali.benali@gmail.com', 'Football'),
('ali.benali@gmail.com', 'Lecture');
