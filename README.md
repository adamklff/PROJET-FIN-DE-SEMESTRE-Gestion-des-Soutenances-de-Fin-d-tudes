# Gestion des Soutenances de Fin d'Études

## 📖 Description du Projet
Ce projet est une application web complète destinée à automatiser et centraliser la gestion des soutenances de fin d'études (PFE). Il permet de coordonner les interactions entre étudiants, professeurs, coordinateurs, directeurs et l'administration.

Les objectifs principaux sont :
* **Centralisation :** Gestion des PFE, affectation des encadrants et suivi.
* **Planification :** Algorithme automatique pour générer les plannings de soutenances sans conflits.
* **Jurys :** Constitution équitable des jurys et gestion des disponibilités.
* **Documents :** Génération automatique des convocations et PVs au format PDF.

## 🛠 Technologies Utilisées
* **Langage Backend :** PHP 8+
* **Base de Données :** MySQL
* **Frontend :** HTML5, CSS3
* **Outils :** GitHub pour le versioning, MAMP/XAMPP pour le serveur local.

## 🚀 Installation et Exécution
1.  Cloner ce dépôt : `git clone https://github.com/votre-username/gestion-soutenances.git`
2.  Importer la base de données :
    * Ouvrir phpMyAdmin.
    * Créer une base nommée `soutenance_db`.
    * Importer le script SQL fourni dans le dossier `/sql` ou à la racine.
3.  Configurer la connexion :
    * Modifier le fichier `config/db.php` avec vos identifiants MySQL.
4.  Lancer le projet via votre serveur local (ex: `http://localhost/gestion-soutenances`).

## 👥 Membres du Groupe et Répartition des Tâches
Ce projet est réalisé par un groupe de 5 étudiants. Voici la répartition officielle des modules :

| Membre | Module | Responsabilités & Livrables |
| :--- | :--- | :--- |
| **Membre 1** | **Backend et Sécurité** | • Authentification & RBAC (Rôles)<br>• Connexion sécurisée BDD<br>• Gestion des sessions |
| **Membre 2** | **Projets et Étudiants** | • Inscription des étudiants & Binômes<br>• Upload et gestion des rapports PDF<br>• Suivi du statut du projet |
| **Membre 3** | **Encadrants** | • Gestion des disponibilités (Calendrier)<br>• Validation des rapports<br>• Consultation des jurys |
| **Membre 4** | **Planning et Jurys** | • Algorithme de génération de planning<br>• Affectation et équilibrage des jurys<br>• Détection des conflits |
| **Membre 5** | **Interface et Documents** | • Design global (UI/UX) & Dashboards<br>• Génération des PDF officiels (Convocations, PV)<br>• Feuilles d'émargement |

*(Note : Remplacez "Membre X" par les noms réels des étudiants dans le tableau ci-dessus)*

## 📂 Architecture du Projet
L'application suit une structure modulaire MVC simplifiée :
* `/auth` : Scripts de connexion/déconnexion.
* `/projets` : Gestion des fiches projets et dépôts.
* `/planning` : Algorithme de planification et vues calendrier.
* `/documents` : Scripts de génération de PDF.
* `/assets` : Feuilles de style CSS et scripts JS.

## ⚙️ Fonctionnalités Clés
* **Algorithme de Planification :** Vérifie la disponibilité des salles, des encadrants et des membres du jury pour proposer des créneaux optimaux.
* **Sécurité :** Contrôle d'accès strict (RBAC) pour 5 rôles (Étudiant, Professeur, Coordinateur, Directeur, Assistante).
* **Digitalisation :** Signature électronique des PVs et archivage numérique.

---
*Année Universitaire : 2025-2026*
