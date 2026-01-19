<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['coordinateur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1);

$plannings = $db->query("
    SELECT s.id, s.date_soutenance, s.heure_debut, p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           sa.nom AS salle
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    JOIN salles sa ON s.salle_id = sa.id
    WHERE p.filiere_id = {$_SESSION['filiere_id']}
      AND p.annee_universitaire = '$annee'
    ORDER BY s.date_soutenance
")->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <h2>Mes plannings de soutenances (<?= $annee ?>)</h2>

    <?php if (empty($plannings)): ?>
        <p>Aucune soutenance planifiée pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Étudiant</th>
                    <th>Projet</th>
                    <th>Salle</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plannings as $p): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($p['date_soutenance'] . ' ' . $p['heure_debut'])) ?></td>
                        <td><?= htmlspecialchars($p['etudiant']) ?></td>
                        <td><?= htmlspecialchars($p['titre']) ?></td>
                        <td><?= htmlspecialchars($p['salle']) ?></td>
                        <td>
                            <a href="generer_pv.php?soutenance_id=<?= $p['id'] ?>" 
                               class="btn btn-success btn-sm">
                                Générer PV
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:2rem;">
        <a href="../dashboard.php" class="btn btn-secondary">Retour au dashboard</a>
    </p>
</div>

<?php include '../includes/footer.php'; ?>