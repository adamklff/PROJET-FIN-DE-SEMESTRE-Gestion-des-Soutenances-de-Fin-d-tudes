<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1); // 2026-2027 par exemple

// Récupère toutes les soutenances où l'utilisateur est jury
$stmt = $db->prepare("
    SELECT 
        s.id AS soutenance_id,
        s.date_soutenance,
        s.heure_debut,
        s.heure_fin,
        s.statut AS statut_sout,
        s.note_finale,
        p.titre,
        CONCAT(e.prenom, ' ', e.nom) AS etudiant,
        j.role_jury,
        j.note_attribuee
    FROM jurys j
    JOIN soutenances s ON j.soutenance_id = s.id
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE j.professeur_id = ?
      AND p.annee_universitaire = ?
    ORDER BY s.date_soutenance DESC
");
$stmt->bind_param("is", $_SESSION['user_id'], $annee);
$stmt->execute();
$jurys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <h2>Mes Jurys (<?= $annee ?>)</h2>

    <?php if (empty($jurys)): ?>
        <p class="text-muted">Aucune soutenance où vous faites partie du jury pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Date & Heure</th>
                        <th>Étudiant</th>
                        <th>Projet</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Votre note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jurys as $jury): ?>
                        <tr>
                            <td>
                                <?= date('d/m/Y', strtotime($jury['date_soutenance'])) ?><br>
                                <small><?= substr($jury['heure_debut'], 0, 5) ?> – <?= substr($jury['heure_fin'], 0, 5) ?></small>
                            </td>
                            <td><?= htmlspecialchars($jury['etudiant']) ?></td>
                            <td><?= htmlspecialchars($jury['titre']) ?></td>
                            <td>
                                <span class="badge bg-info"><?= ucfirst($jury['role_jury']) ?></span>
                            </td>
                            <td>
                                <?php
                                $statut = $jury['statut_sout'];
                                $badge = match($statut) {
                                    'planifiee' => 'bg-warning text-dark',
                                    'terminee'  => 'bg-success',
                                    'soutenu'   => 'bg-primary',
                                    default     => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge ?>">
                                    <?= ucfirst($statut) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($jury['note_attribuee'] !== null): ?>
                                    <strong><?= $jury['note_attribuee'] ?>/20</strong>
                                <?php else: ?>
                                    <span class="text-muted">Non saisi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($jury['note_attribuee'] === null && in_array($jury['statut_sout'], ['planifiee', 'terminee'])): ?>
                                    <a href="saisir_note.php?soutenance_id=<?= $jury['soutenance_id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        Saisir ma note
                                    </a>
                                <?php elseif ($jury['statut_sout'] === 'soutenu'): ?>
                                    <a href="../coordinateur/generer_pv.php?soutenance_id=<?= $jury['soutenance_id'] ?>" 
                                       class="btn btn-success btn-sm" target="_blank">
                                        Voir le PV
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
        <a href="../dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>