<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$projet_id = (int)($_GET['projet_id'] ?? 0);

if ($projet_id <= 0) {
    die("<div class='error card'>Projet non spécifié.</div>");
}

$db = getDB();

// Vérifie que c'est bien le projet de ce prof et rapport soumis
$stmt = $db->prepare("
    SELECT p.titre, p.statut, r.id AS rapport_id, r.version, r.chemin, r.resume
    FROM projets p
    LEFT JOIN rapports r ON p.id = r.projet_id
    WHERE p.id = ? AND p.encadrant_id = ? AND p.statut = 'rapport_soumis'
    ORDER BY r.version DESC LIMIT 1
");
$stmt->bind_param("ii", $projet_id, $_SESSION['user_id']);
$stmt->execute();
$rapport = $stmt->get_result()->fetch_assoc();

if (!$rapport) {
    die("<div class='error card'>Rapport non trouvé ou accès refusé.</div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valide = isset($_POST['valider']) ? 1 : 0;
    $commentaire = sanitize($_POST['commentaire'] ?? '');

    $stmt = $db->prepare("
        UPDATE rapports 
        SET valide_encadrant = ?, commentaire_encadrant = ?, date_validation = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("isi", $valide, $commentaire, $rapport['rapport_id']);
    $stmt->execute();

    $nouveau_statut = $valide ? 'valide_encadrant' : 'rapport_soumis';
    $db->query("UPDATE projets SET statut = '$nouveau_statut' WHERE id = $projet_id");

    $_SESSION['success'] = $valide ? "Rapport validé ! Le projet est prêt pour planification." : "Rapport refusé.";
    header('Location: mes_projets.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <h2>Validation du rapport – <?= htmlspecialchars($rapport['titre']) ?></h2>

    <div style="background:#f9f9f9; padding:1.5rem; border-radius:8px; margin:1.5rem 0;">
        <p><strong>Version :</strong> <?= $rapport['version'] ?></p>
        <p><strong>Résumé :</strong> <?= nl2br(htmlspecialchars($rapport['resume'] ?? 'Non fourni')) ?></p>
        <p><strong>Fichier :</strong> 
            <a href="<?= htmlspecialchars($rapport['chemin']) ?>" target="_blank" class="btn btn-primary btn-sm">
                Télécharger le PDF
            </a>
        </p>
    </div>

    <form method="POST">
        <label>
            <input type="radio" name="valider" value="1" required> 
            <strong>VALIDER</strong> – Le rapport est prêt pour la soutenance
        </label><br><br>

        <label>
            <input type="radio" name="valider" value="0"> 
            <strong>REFUSER</strong> – Demander des modifications
        </label>

        <div style="margin-top:1.5rem;">
            <label for="commentaire">Commentaires (obligatoire si refus) :</label><br>
            <textarea name="commentaire" rows="5" style="width:100%;"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1.5rem;">Confirmer</button>
        <a href="mes_projets.php" class="btn btn-secondary" style="margin-left:1rem;">Annuler</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>