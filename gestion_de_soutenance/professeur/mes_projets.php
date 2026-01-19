<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1); // 2025-2026

// Récupérer les projets encadrés par ce prof
$stmt = $db->prepare("
    SELECT p.id, p.titre, p.mots_cles, p.statut, p.date_affectation,
           CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           e.email AS etudiant_email,
           s.id AS soutenance_id
    FROM projets p
    JOIN utilisateurs e ON p.etudiant_id = e.id
    LEFT JOIN soutenances s ON p.id = s.projet_id
    WHERE p.encadrant_id = ? 
      AND p.filiere_id = ? 
      AND p.annee_universitaire = ?
    ORDER BY p.statut ASC, p.date_affectation DESC
");
$stmt->bind_param("iis", $_SESSION['user_id'], $_SESSION['filiere_id'], $annee);
$stmt->execute();
$projets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<main style="max-width:1000px; margin:30px auto;">
    <div class="card">
        <h2>Mes projets encadrés (<?= $annee ?>)</h2>
        
        <div style="background:#f9f9f9; padding:15px; margin-bottom:20px; border-radius:6px;">
            <strong>Charge actuelle :</strong> <?= count($projets) ?>/<?= $_SESSION['max_encadrements'] ?? 5 ?> projets
        </div>

        <?php if (empty($projets)): ?>
            <p>Aucun projet affecté pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Titre</th>
                        <th>Mots-clés</th>
                        <th>Statut</th>
                        <th>Date affectation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projets as $projet): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td><?= htmlspecialchars($projet['etudiant']) ?><br>
                                <small><?= htmlspecialchars($projet['etudiant_email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($projet['titre']) ?></td>
                            <td><?= htmlspecialchars($projet['mots_cles']) ?></td>
                            <td>
                                <span style="font-weight:bold; <?= getStatusColor($projet['statut']) ?>">
                                    <?= formatStatut($projet['statut']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($projet['date_affectation'])) ?></td>
                            <td>
                                <?php if ($projet['statut'] === 'rapport_soumis'): ?>
                                    <a href="../professeur/valider_rapport.php?projet_id=<?= $projet['id'] ?>" 
                                    class="btn btn-primary btn-sm" style="padding:6px 12px; font-size:0.9rem;">
                                        Valider rapport
                                    </a>
                                <?php elseif ($projet['statut'] === 'valide_encadrant'): ?>
                                    <span class="success" style="color:#27ae60; font-weight:bold;">Rapport validé</span>
                                <?php elseif (!empty($projet['soutenance_id'])): ?>
                                    <a href="saisir_note.php?soutenance_id=<?= $projet['soutenance_id'] ?>" 
                                    class="btn btn-link">
                                        Saisir une note
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p style="margin-top:30px;">
            <a href="../dashboard.php" class="secondary">← Retour au tableau de bord</a>
        </p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>