# Gestion des soutenances de fin d’études

Application web de groupe en PHP et MySQL pour organiser les soutenances de projets de fin d’études. Les étudiants déposent leur rapport, les enseignants indiquent leurs disponibilités et les coordinateurs planifient les soutenances et composent les jurys.

## Fonctionnalités

- Dépôt et validation des rapports, affectation des encadrants et saisie des notes.
- Planification manuelle avec contrôles de conflits et gestion des jurys.
- Génération des convocations et procès-verbaux en PDF avec FPDF.

## Structure du dépôt

Le code se trouve dans [`gestion_de_soutenance/`](gestion_de_soutenance/). Les dossiers `etudiant/`, `professeur/`, `coordinateur/` et `directeur/` regroupent les pages par rôle. Le [rapport de projet](Rapport_PFE_Gestion_Soutenances.pdf) décrit le contexte académique.

## État du projet

Le projet utilise PHP, l’extension MySQLi et MySQL. Le fichier [`database.sql`](gestion_de_soutenance/data_base/database.sql) est vide dans la version actuelle. Pour exécuter le projet localement, il faut d’abord disposer d’un schéma et de données de démonstration, puis configurer la connexion MySQL. L’envoi d’e-mails nécessite aussi une configuration SMTP locale.

## Équipe

| Membre | Responsabilités dans le projet |
| --- | --- |
| Abderrahmane Ait Ben Haddou | Authentification, rôles et sessions |
| Douae Bahaja | Inscription, dépôt des rapports et suivi des projets |
| Marwa El Khoudari | Disponibilités, validation des rapports et notation |
| Adam Khlifi | Planification, affectation des jurys et gestion des conflits |
| Malak Boulahbach | Interface, tableaux de bord et documents PDF |

Projet de développement web, année universitaire 2025–2026.
