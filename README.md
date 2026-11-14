# Gestion des soutenances de fin d’études

Application web de groupe en PHP et MySQL pour organiser les soutenances de projets de fin d’études. Les étudiants déposent leur rapport, les enseignants indiquent leurs disponibilités et les coordinateurs planifient les soutenances et composent les jurys.

## Fonctionnalités

- Dépôt et validation des rapports, affectation des encadrants et saisie des notes.
- Planification manuelle avec contrôles de conflits et gestion des jurys.
- Génération des convocations et procès-verbaux en PDF avec FPDF.

## Structure du dépôt

Le code se trouve dans [`gestion_de_soutenance/`](gestion_de_soutenance/). Les dossiers `etudiant/`, `professeur/`, `coordinateur/` et `directeur/` regroupent les pages par rôle. Le [rapport de projet](Rapport_PFE_Gestion_Soutenances.pdf) décrit le contexte académique.

## Lancer la démo en local

Il faut PHP 8 avec MySQLi et un serveur MySQL ou MariaDB. Créez une base vide nommée `gestion_soutenances`, puis importez [le schéma](gestion_de_soutenance/data_base/database.sql). Depuis la racine du dépôt, avec Bash et un client MySQL configuré pour votre installation :

```sh
mysql -u root -p -e "CREATE DATABASE gestion_soutenances CHARACTER SET utf8mb4"
mysql -u root -p gestion_soutenances < gestion_de_soutenance/data_base/database.sql
read -rsp "Mot de passe de démo (12 caractères minimum) : " DEMO_PASSWORD
export DEMO_PASSWORD
php gestion_de_soutenance/data_base/seed_demo.php
unset DEMO_PASSWORD
php -S 127.0.0.1:8000 -t gestion_de_soutenance
```

Définissez `DB_HOST`, `DB_USER`, `DB_PASS` ou `DB_NAME` selon votre installation avant de lancer PHP. Ouvrez `http://127.0.0.1:8000/` et connectez-vous avec `etudiant@example.test`, `professeur@example.test`, `coordinateur@example.test`, `directeur@example.test` ou `assistante@example.test` et le mot de passe choisi. Les comptes et la salle sont des données fictives. Les paramètres `SMTP_USERNAME` et `SMTP_PASSWORD` activent l’envoi d’e-mails. `SMTP_HOST`, `SMTP_PORT` et `SMTP_FROM` sont facultatifs.

Cette démo académique est prévue pour une utilisation locale. Le schéma a été reconstruit à partir des requêtes PHP avec l’aide de l’IA.

## Équipe

| Membre | Responsabilités dans le projet |
| --- | --- |
| Abderrahmane Ait Ben Haddou | Authentification, rôles et sessions |
| Douae Bahaja | Inscription, dépôt des rapports et suivi des projets |
| Marwa El Khoudari | Disponibilités, validation des rapports et notation |
| Adam Khlifi | Planification, affectation des jurys et gestion des conflits |
| Malak Boulahbach | Interface, tableaux de bord et documents PDF |

Projet de développement web, année universitaire 2025–2026.
