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
* **Outils :** GitHub pour le versioning, MAMP/XAMPP pour le serveur local, FPDF pour la génération de documents.

## 🚀 Instructions d'Installation et d'Exécution
1.  **Cloner le dépôt :**
    ```bash
    git clone [https://github.com/votre-username/votre-repo.git](https://github.com/votre-username/votre-repo.git)
    ```
2.  **Importer la Base de Données :**
    * Ouvrir phpMyAdmin.
    * Créer une base de données vide nommée `gestion_soutenances` (ou le nom dans votre config).
    * Importer le fichier `.sql` fourni à la racine du projet.
3.  **Configurer la connexion :**
    * Ouvrir le fichier `includes/config.php` (ou `config/db.php`).
    * Vérifier les identifiants (`root`, mot de passe vide ou `root`).
4.  **Lancer le projet :**
    * Placer le dossier du projet dans `htdocs` (XAMPP) ou `www` (MAMP/WAMP).
    * Accéder via le navigateur : `http://localhost/nom-du-dossier`.

## 👥 Membres du Groupe et Répartition des Tâches

| Membre | Module | Responsabilités Principales |
| :--- | :--- | :--- |
| **AIT BEN HADDOU Abderrahmane** | **Backend et Sécurité** | • Authentification & RBAC (Rôles)<br>• Connexion sécurisée BDD<br>• Gestion des sessions |
| **BAHAJA Douae** | **Projets et Étudiants** | • Inscription des étudiants & Binômes<br>• Upload et gestion des rapports<br>• Suivi du statut du projet |
| **EL KHOUDARI Marwa** | **Encadrants** | • Gestion des disponibilités (Calendrier)<br>• Validation des rapports<br>• Notation et Feedback |
| **KHLIFI Adam** | **Planning et Jurys** | • Algorithme de génération de planning<br>• Affectation et équilibrage des jurys<br>• Gestion des salles et conflits |
| **BOULAHBACH Malak** | **Interface et Documents** | • Design global (UI/UX) & Dashboards<br>• Génération des PDF officiels (Convocations, PV)<br>• Feuilles d'émargement |

## 📂 Architecture du Projet
L'application suit une structure modulaire :
* `/auth` : Scripts de connexion/déconnexion.
* `/projets` : Gestion des fiches projets et dépôts.
* `/encadrants` : Espace professeur pour validation.
* `/planning` : Algorithme de planification.
* `/documents` : Scripts de génération de PDF.
* `/assets` : Feuilles de style CSS et scripts JS.

---
*Projet réalisé dans le cadre du module de Développement Web - Année Universitaire 2025-2026*
