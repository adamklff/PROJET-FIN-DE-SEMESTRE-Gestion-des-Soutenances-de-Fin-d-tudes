-- Fresh local schema inferred from the application queries.
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL,
    filiere_id INT UNSIGNED NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    capacite INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    mots_cles VARCHAR(255) NOT NULL DEFAULT '',
    etudiant_id INT UNSIGNED NOT NULL,
    encadrant_id INT UNSIGNED NULL,
    filiere_id INT UNSIGNED NOT NULL,
    annee_universitaire CHAR(9) NOT NULL,
    statut VARCHAR(32) NOT NULL DEFAULT 'inscrit',
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_affectation DATETIME NULL,
    binome_email VARCHAR(254) NULL,
    UNIQUE (etudiant_id, annee_universitaire),
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (encadrant_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rapports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projet_id INT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(500) NOT NULL,
    taille INT UNSIGNED NOT NULL,
    resume TEXT NULL,
    valide_encadrant TINYINT(1) NOT NULL DEFAULT 0,
    commentaire_encadrant TEXT NULL,
    date_validation DATETIME NULL,
    UNIQUE (projet_id, version),
    FOREIGN KEY (projet_id) REFERENCES projets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS soutenances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projet_id INT UNSIGNED NOT NULL UNIQUE,
    salle_id INT UNSIGNED NOT NULL,
    date_soutenance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    statut VARCHAR(32) NOT NULL DEFAULT 'planifiee',
    note_finale DECIMAL(5,2) NULL,
    mention VARCHAR(64) NULL,
    commentaire_jury TEXT NULL,
    signe_directeur TINYINT(1) NOT NULL DEFAULT 0,
    date_signature DATETIME NULL,
    INDEX (salle_id, date_soutenance, heure_debut),
    FOREIGN KEY (projet_id) REFERENCES projets(id),
    FOREIGN KEY (salle_id) REFERENCES salles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jurys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    soutenance_id INT UNSIGNED NOT NULL,
    professeur_id INT UNSIGNED NOT NULL,
    role_jury VARCHAR(32) NOT NULL,
    note_attribuee DECIMAL(5,2) NULL,
    appreciation TEXT NULL,
    presence_confirmee TINYINT(1) NOT NULL DEFAULT 0,
    date_notation DATETIME NULL,
    UNIQUE (soutenance_id, professeur_id),
    FOREIGN KEY (soutenance_id) REFERENCES soutenances(id),
    FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS periodes_disponibilite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filiere_id INT UNSIGNED NOT NULL,
    annee_universitaire CHAR(9) NOT NULL,
    date_debut_saisie DATE NOT NULL,
    date_fin_saisie DATE NOT NULL,
    date_debut_soutenances DATE NOT NULL,
    date_fin_soutenances DATE NOT NULL,
    statut VARCHAR(32) NOT NULL DEFAULT 'a_venir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disponibilites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professeur_id INT UNSIGNED NOT NULL,
    periode_id INT UNSIGNED NOT NULL,
    date_disponible DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    UNIQUE (professeur_id, periode_id, date_disponible, heure_debut),
    FOREIGN KEY (professeur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (periode_id) REFERENCES periodes_disponibilite(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS remember_tokens (
    user_id INT UNSIGNED PRIMARY KEY,
    token VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(254) PRIMARY KEY,
    token VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (email) REFERENCES utilisateurs(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
