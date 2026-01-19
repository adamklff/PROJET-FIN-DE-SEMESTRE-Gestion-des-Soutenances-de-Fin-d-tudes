<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['directeur']);

$db = getDB();

// Liste des PV en attente de signature (statut 'soutenu' mais pas signé)
$pv_en_attente = $db->query("
    SELECT s.id, s.date_soutenance, p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           s.note_finale, s.mention
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE s.statut = 'soutenu' AND s.signe_directeur = 0
    ORDER BY s.date_soutenance
")->fetch_all(MYSQLI_ASSOC);

// Action signature (simulée)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signer'])) {
    $sout_id = (int)$_POST['soutenance_id'];
    $db->query("UPDATE soutenances SET signe_directeur = 1, date_signature = NOW() WHERE id = $sout_id");
    header("Location: signer_pv.php?success=1");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Signature des Procès-Verbaux</h4>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">PV signé avec succès !</div>
            <?php endif; ?>

            <?php if (empty($pv_en_attente)): ?>
                <div class="alert alert-info text-center">
                    Aucun PV en attente de signature.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Étudiant</th>
                                <th>Projet</th>
                                <th>Note</th>
                                <th>Mention</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pv_en_attente as $pv): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($pv['date_soutenance'])) ?></td>
                                    <td><?= htmlspecialchars($pv['etudiant']) ?></td>
                                    <td><?= htmlspecialchars($pv['titre']) ?></td>
                                    <td><?= $pv['note_finale'] ?>/20</td>
                                    <td><?= htmlspecialchars($pv['mention']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="soutenance_id" value="<?= $pv['id'] ?>">
                                            <button type="submit" name="signer" class="btn btn-success btn-sm">
                                                Signer PV
                                            </button>
                                        </form>
                                        <a href="../coordinateur/generer_pv.php?soutenance_id=<?= $pv['id'] ?>" 
                                           class="btn btn-info btn-sm ms-2" target="_blank">
                                            Voir PV
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="../dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>