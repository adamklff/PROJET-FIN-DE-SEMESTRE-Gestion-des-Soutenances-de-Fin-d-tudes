<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['directeur']);

$db = getDB();

// Récupère les soutenances non validées par le directeur
$soutenances = $db->query("
    SELECT s.id, s.date_soutenance, s.heure_debut, s.heure_fin, s.statut,
           p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           sa.nom AS salle
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    JOIN salles sa ON s.salle_id = sa.id
    WHERE s.statut IN ('planifiee', 'terminee')
    ORDER BY s.date_soutenance
")->fetch_all(MYSQLI_ASSOC);

// Action validation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider'])) {
    $sout_id = (int)$_POST['soutenance_id'];
    $db->query("UPDATE soutenances SET statut = 'validee_directeur' WHERE id = $sout_id");
    header("Location: planning.php?success=1");
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Validation du planning des soutenances</h4>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Planning validé avec succès !</div>
            <?php endif; ?>

            <?php if (empty($soutenances)): ?>
                <div class="alert alert-info text-center">
                    Aucun planning en attente de validation.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Date & Heure</th>
                                <th>Étudiant</th>
                                <th>Projet</th>
                                <th>Salle</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($soutenances as $s): ?>
                                <tr>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($s['date_soutenance'] . ' ' . $s['heure_debut'])) ?>
                                    </td>
                                    <td><?= htmlspecialchars($s['etudiant']) ?></td>
                                    <td><?= htmlspecialchars($s['titre']) ?></td>
                                    <td><?= htmlspecialchars($s['salle']) ?></td>
                                    <td>
                                        <span class="badge bg-warning"><?= ucfirst($s['statut']) ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="soutenance_id" value="<?= $s['id'] ?>">
                                            <button type="submit" name="valider" class="btn btn-success btn-sm">
                                                Valider
                                            </button>
                                        </form>
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
</div>

<?php include '../includes/footer.php'; ?>