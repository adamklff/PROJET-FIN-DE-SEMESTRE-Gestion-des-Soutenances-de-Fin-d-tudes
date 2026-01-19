<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['coordinateur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1); // 2025-2026 par exemple

// Récupérer tous les projets en attente d'encadrant pour cette filière
$query = "
    SELECT p.id, p.titre, p.mots_cles, p.description, p.date_inscription,
           CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           e.email AS etudiant_email
    FROM projets p
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE p.filiere_id = ? 
    AND p.annee_universitaire = ?
    AND p.statut = 'inscrit'
    AND p.encadrant_id IS NULL
    ORDER BY p.date_inscription ASC
";

$stmt = $db->prepare($query);
$stmt->bind_param("is", $_SESSION['filiere_id'], $annee);
$stmt->execute();
$projets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Récupérer la liste des professeurs disponibles (même filière)
$profs = $db->query("
    SELECT id, CONCAT(prenom, ' ', nom) AS nom_complet, 
           max_encadrements,
           (SELECT COUNT(*) FROM projets WHERE encadrant_id = utilisateurs.id 
            AND annee_universitaire = '$annee') AS nb_actuel
    FROM utilisateurs 
    WHERE role = 'professeur' 
    AND filiere_id = " . (int)$_SESSION['filiere_id'] . "
    AND actif = 1
    ORDER BY nom_complet
");

?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<main style="max-width:1100px; margin:30px auto;">
    <div class="card">
    <h2>Projets en attente d'encadrant (<?= $annee ?>)</h2>

    <?php if (empty($projets)): ?>
        <p class="success" style="background:#e8f5e9; padding:15px;">
            Aucun projet en attente d'affectation pour le moment.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Titre</th>
                    <th>Mots-clés</th>
                    <th>Date inscription</th>
                    <th>Affecter encadrant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projets as $projet): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($projet['etudiant']) ?><br>
                            <small><?= htmlspecialchars($projet['etudiant_email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($projet['titre']) ?></td>
                        <td><?= htmlspecialchars($projet['mots_cles']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($projet['date_inscription'])) ?></td>
                        <td>
                            <form method="POST" action="affecter_encadrant.php" style="margin:0;">
                                <input type="hidden" name="projet_id" value="<?= $projet['id'] ?>">
                                <select name="encadrant_id" required>
                                    <option value="">-- Choisir --</option>
                                    <?php 
                                    $profs->data_seek(0);
                                    while ($prof = $profs->fetch_assoc()): 
                                        $charge = $prof['nb_actuel'];
                                        $max = $prof['max_encadrements'] ?: 5;
                                        $texte = $prof['nom_complet'] . " ($charge/$max)";
                                    ?>
                                        <option value="<?= $prof['id'] ?>">
                                            <?= htmlspecialchars($texte) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" style="padding:6px 12px; font-size:0.9em;">
                                    Affecter
                                </button>
                            </form>
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