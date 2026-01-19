<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireLogin(); // Redirect to login if not authenticated

$role = getUserRole();

include 'includes/header.php';
?>

<main>
    <div class="card">
    <h2>Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?> !</h2>
    <p>Vous êtes connecté en tant que : <strong><?= ucfirst($role) ?></strong></p>

    <?php if ($role === 'etudiant'): ?>
        <h3>Tableau de bord Étudiant</h3>

        <?php
        $annee = date('Y') . '-' . (date('Y') + 1); // 2026-2027 dans ton cas
        $db = getDB();

        $stmt = $db->prepare("
            SELECT p.id, p.titre, p.description, p.mots_cles, p.statut, 
                u.prenom AS encadrant_prenom, u.nom AS encadrant_nom,
                s.note_finale, s.mention, s.statut AS statut_soutenance
            FROM projets p
            LEFT JOIN utilisateurs u ON p.encadrant_id = u.id
            LEFT JOIN soutenances s ON p.id = s.projet_id
            WHERE p.etudiant_id = ? 
            AND p.annee_universitaire = ?
            LIMIT 1
        ");

        $stmt->bind_param("is", $_SESSION['user_id'], $annee);
        $stmt->execute();
        $projet = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        ?>

        <?php if ($projet): ?>
            <div style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:6px; margin:20px 0;">
                <h4>Votre projet actuel (<?= htmlspecialchars($annee) ?>)</h4>
                <p><strong>Titre :</strong> <?= htmlspecialchars($projet['titre']) ?></p>
                <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($projet['description'])) ?></p>
                <p><strong>Mots-clés :</strong> <?= htmlspecialchars($projet['mots_cles']) ?></p>
                
                <p style="margin-top:15px;">
                    <strong>Statut actuel :</strong> 
                    <span style="font-weight:bold; <?= getStatusColor($projet['statut']) ?>">
                        <?= formatStatut($projet['statut']) ?>
                    </span>
                </p>

                <?php if ($projet['encadrant_nom']): ?>
                    <p><strong>Encadrant affecté :</strong> 
                    <?= htmlspecialchars($projet['encadrant_prenom'] . ' ' . $projet['encadrant_nom']) ?>
                    </p>
                <?php else: ?>
                    <p><em>En attente d'affectation d'un encadrant...</em></p>
                <?php endif; ?>

                <!-- Section actions selon statut -->
                <div style="margin-top:25px;">
                    <?php if ($projet['statut'] === 'encadrant_affecte'): ?>
                        <p style="margin:10px 0;">
                            <strong>Prochaine étape :</strong> Soumettre votre rapport final
                        </p>
                        <a href="etudiant/soumettre_rapport.php" class="btn btn-success"
                        style="background:#3498db; color:white; padding:10px 20px; text-decoration:none; border-radius:4px; display:inline-block;">
                            Soumettre le rapport maintenant →
                        </a>
                    <?php elseif ($projet['statut'] === 'rapport_soumis'): ?>
                        <p><strong>Statut du rapport :</strong> En attente de validation par l'encadrant</p>
                    <?php elseif ($projet['statut'] === 'valide_encadrant'): ?>
                        <p class="success">✅ Rapport validé ! Vous êtes prêt pour la planification de la soutenance.</p>
                    <?php elseif ($projet['statut'] === 'planifie'): ?>
                        <p><strong>Soutenance planifiée :</strong> <a href="#">Voir les détails</a></p>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <p style="font-size:1.1em; color:#555;">
                Vous n'avez pas encore inscrit de projet pour l'année <?= $annee ?>.
            </p>
            <p>
                <a href="etudiant/inscription.php" class="btn btn-success" style="background:#4CAF50; color:white; padding:10px 20px; text-decoration:none; border-radius:4px; display:inline-block;">
                    Inscrire mon PFE maintenant →
                </a>
            </p>
        <?php endif; ?>
        <?php if (!empty($projet['soutenance_id'])): ?>
            <div style="margin-top:2rem; text-align:center;">
                <a href="etudiant/telecharger_convocation.php" class="btn btn-primary" style="padding:12px 30px; font-size:1.1rem;">
                    Télécharger ma convocation officielle (PDF)
                </a>
            </div>
        <?php elseif ($projet): ?>
            <p style="margin-top:1.5rem; color:#666; font-style:italic; text-align:center;">
                La convocation sera disponible une fois la soutenance planifiée.
            </p>
        <?php endif; ?>
        <?php if ($projet && $projet['statut'] === 'soutenu'): ?>
            <div class="card success" style="margin-top:2rem; background:#d4edda; border:1px solid #c3e6cb; padding:1.5rem; border-radius:8px;">
                <h3 style="color:#155724; margin-bottom:1rem;">Félicitations ! Votre soutenance est terminée</h3>
                
                <p><strong>Note finale :</strong> 
                    <span style="font-size:1.4rem; font-weight:bold; color:#27ae60;">
                        <?= $projet['note_finale'] ?? '—' ?>/20
                    </span>
                </p>
                
                <p><strong>Mention :</strong> 
                    <strong><?= $projet['mention'] ?? 'Non attribuée' ?></strong>
                </p>
                
                <?php if (!empty($projet['commentaire_jury'])): ?>
                    <p><strong>Commentaire du jury :</strong><br>
                        <?= nl2br(htmlspecialchars($projet['commentaire_jury'])) ?>
                    </p>
                <?php endif; ?>
                
                <div style="margin-top:1.5rem; text-align:center;">
                    <a href="etudiant/telecharger_pv.php" class="btn btn-success" style="padding:12px 30px;">
                        Télécharger mon Procès-Verbal officiel (PDF)
                    </a>
                </div>
                <div style="margin-top: 2rem;">
                    <a href="etudiant/resultats.php" class="btn btn-info">
                        Voir mes résultats de soutenance
                    </a>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($role === 'professeur'): ?>
        <h3>Tableau de bord Professeur</h3>
        <ul>
            <li><a href="professeur/mes_projets.php" class="btn-link">Mes projets encadrés</a></li>
            <li><a href="professeur/mes_disponibilites.php" class="btn-link">Saisir disponibilités jury</a></li>
            <li><a href="professeur/mes_jurys.php" class="btn-link"> Mes jurys planifiés</a></li>
        </ul>
    

    <?php elseif ($role === 'coordinateur'): ?>
        <h3>Tableau de bord Coordinateur</h3>
        <ul>
            <li><a href="coordinateur/projets_en_attente.php" class="btn-link">Projets en attente d'encadrant</a></li>
            <li><a href="coordinateur/gerer_periodes_disponibilite.php" class="btn btn-link">Gérer les périodes de disponibilités</a></li>
            <li><a href="coordinateur/planification_manuelle.php" class="btn btn-link">Planification manuelle des soutenances</a></li>
            <li><a href="coordinateur/mes_plannings.php" class="btn btn-link">Voir les soutenances planifiées</a></li>
            <!-- autres liens... -->
        </ul>
    

    <?php elseif ($role === 'directeur'): ?>
        <div class="card">
            <div class="text-center mb-5">
                <h1 class="display-4 text-primary">Bienvenue, Directeur Test !</h1>
                <p class="lead text-muted">Vous êtes connecté en tant que : <strong>Directeur</strong></p>
            </div>

            <?php
            $db = getDB();

            // Calcul des vraies stats
            $total_soutenances = $db->query("SELECT COUNT(*) FROM soutenances")->fetch_row()[0];
            $planifiees = $db->query("SELECT COUNT(*) FROM soutenances WHERE statut IN ('planifiee', 'validee_directeur')")->fetch_row()[0];
            $terminees = $db->query("SELECT COUNT(*) FROM soutenances WHERE statut IN ('soutenu', 'terminee')")->fetch_row()[0];
            $taux_reussite = $terminees > 0 ? round(($db->query("SELECT COUNT(*) FROM soutenances WHERE statut IN ('soutenu', 'terminee') AND note_finale >= 10")->fetch_row()[0] / $terminees) * 100, 1) : 0;
            $sans_encadrant = $db->query("SELECT COUNT(*) FROM projets WHERE encadrant_id IS NULL")->fetch_row()[0];
            ?>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card shadow border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Soutenances planifiées</h5>
                            <h2 class="card-text"><?= $planifiees ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-success h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success">Soutenances terminées</h5>
                            <h2 class="card-text"><?= $terminees ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-info h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info">Taux de réussite moyen</h5>
                            <h2 class="card-text"><?= $taux_reussite ?> %</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-danger h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-danger">Projets sans encadrant</h5>
                            <h2 class="card-text"><?= $sans_encadrant ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="directeur/planning.php" class="btn btn-primary btn-lg me-3">
                    Valider le planning
                </a>
                <a href="directeur/stats.php" class="btn btn-info btn-lg">
                    Voir les statistiques détaillées
                </a>
            </div>
            </div>


    <?php elseif ($role === 'assistante'): ?>
        <div class="card">
        <h3>Tableau de bord Assistante Administrative</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Gestion des salles</h5>
                        <p>Salles réservées aujourd'hui : <strong>XX</strong></p>
                        <p>Conflits détectés : <strong>0</strong></p>
                        <a href="assistante/salles.php" class="btn btn-link mt-2">Voir les réservations</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Préparation des dossiers</h5>
                        <p>Dossiers à préparer : <strong>XX</strong></p>
                        <a href="assistante/dossiers.php" class="btn btn-warning mt-2">Voir les dossiers</a>
                        <a href="assistante/archives.php" class="btn btn-info mt-2">Archivage</a>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <?php else: ?>
        <p class="error">Rôle non reconnu. Contactez l'administrateur.</p>
    <?php endif; ?>

    <p style="margin-top:40px;">
        <a href="logout.php" class="btn btn-link" style="color:#c00; font-weight:bold;">Déconnexion</a>
    </p>
    </div>
</main>

<?php include 'includes/footer.php'; ?>