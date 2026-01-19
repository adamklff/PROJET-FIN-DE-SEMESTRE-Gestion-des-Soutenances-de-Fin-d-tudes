<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['etudiant']);

$db = getDB();

// Récupère la dernière soutenance de l'étudiant
$stmt = $db->prepare("
    SELECT 
        s.id AS soutenance_id,
        s.date_soutenance,
        s.heure_debut,
        s.heure_fin,
        s.statut AS statut_sout,
        s.note_finale,
        s.mention,
        p.titre,
        CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE p.etudiant_id = ?
    ORDER BY s.date_soutenance DESC
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$sout = $stmt->get_result()->fetch_assoc();

if (!$sout) {
    $message = "Aucune soutenance trouvée pour votre compte.";
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <h2>Résultats de ma soutenance</h2>

    <?php if (!$sout): ?>
        <div class="alert alert-info">
            <?= $message ?>
            <p class="mt-3">
                <a href="../dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </p>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <h4>Projet : <?= htmlspecialchars($sout['titre']) ?></h4>
                <p><strong>Date de la soutenance :</strong> 
                    <?= date('d/m/Y', strtotime($sout['date_soutenance'])) ?> 
                    de <?= substr($sout['heure_debut'], 0, 5) ?> à <?= substr($sout['heure_fin'], 0, 5) ?>
                </p>

                <p><strong>Statut actuel :</strong> 
                    <span class="badge <?= $sout['statut_sout'] === 'soutenu' || $sout['statut_sout'] === 'terminee' ? 'bg-success' : 'bg-warning' ?>">
                        <?= ucfirst($sout['statut_sout']) ?>
                    </span>
                </p>

                <?php if ($sout['note_finale'] !== null): ?>
                    <div class="alert alert-success mt-4">
                        <h4 class="alert-heading">Résultats finaux</h4>
                        <p class="mb-1"><strong>Note finale :</strong> <?= $sout['note_finale'] ?>/20</p>
                        <p class="mb-0"><strong>Mention :</strong> <?= htmlspecialchars($sout['mention']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <strong>En attente des résultats</strong><br>
                        Les notes sont en cours de saisie par le jury. Vous serez notifié une fois la note finale calculée.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4 text-center mt-4 mt-md-0">
                <?php if ($sout['note_finale'] !== null): ?>
                    <a href="telecharger_pv.php?soutenance_id=<?= $sout['soutenance_id'] ?>" 
                       class="btn btn-link btn-lg" style="padding:15px 30px;">
                        <i class="fas fa-file-pdf me-2"></i>
                        Télécharger le PV officiel (PDF)
                    </a>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        Le PV sera disponible une fois la note finale calculée.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lien retour -->
        <div style="margin-top: 2rem;">
            <a href="../dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>