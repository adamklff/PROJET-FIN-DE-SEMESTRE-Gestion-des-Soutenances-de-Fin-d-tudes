<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['etudiant']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1);
$projet_id = 0;
$projet = null;

$stmt = $db->prepare("SELECT id, statut FROM projets WHERE etudiant_id = ? AND annee_universitaire = ?");
$stmt->bind_param("is", $_SESSION['user_id'], $annee);
$stmt->execute();
$projet = $stmt->get_result()->fetch_assoc();

if (!$projet || $projet['statut'] !== 'encadrant_affecte') {
    $_SESSION['error'] = "Rapport non disponible. Votre projet doit être affecté à un encadrant.";
    header('Location: ../dashboard.php');
    exit;
}
$projet_id = $projet['id'];

// Vérifier si rapport déjà soumis
$rapport_actuel = $db->query("SELECT version, valide_encadrant FROM rapports WHERE projet_id = $projet_id ORDER BY version DESC LIMIT 1")->fetch_assoc();

// Traitement upload
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['rapport'])) {
    $file = $_FILES['rapport'];
    
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 50 * 1024 * 1024) { // 50Mo
        if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'pdf') {
            $upload_dir = '../uploads/rapports/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $version = ($rapport_actuel ? $rapport_actuel['version'] + 1 : 1);
            $nom_fichier = 'rapport_' . $projet_id . '_v' . $version . '.pdf';
            $chemin = $upload_dir . $nom_fichier;
            
            if (move_uploaded_file($file['tmp_name'], $chemin)) {
                $stmt = $db->prepare("INSERT INTO rapports (projet_id, version, nom_fichier, chemin, taille, resume) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissis", $projet_id, $version, $nom_fichier, $chemin, $file['size'], 
                                sanitize($_POST['resume'] ?? ''));
                if ($stmt->execute()) {
                    $db->query("UPDATE projets SET statut = 'rapport_soumis' WHERE id = $projet_id");
                    $success = "Rapport soumis avec succès (version $version) ! En attente validation encadrant.";
                }
            }
        } else {
            $error = "Seul le format PDF est accepté.";
        }
    } else {
        $error = "Erreur upload ou fichier trop volumineux (max 50Mo).";
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<main style="max-width:700px; margin:30px auto;">
    <div class="card">
    <h2>Soumission Rapport Final</h2>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
        <p><a href="../dashboard.php" class="secondary">← Retour dashboard</a></p>
    <?php elseif ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if (!$rapport_actuel || !$rapport_actuel['valide_encadrant']): ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <div>
                <label>Rapport final (PDF, max 50Mo) * :</label><br>
                <input type="file" name="rapport" accept=".pdf" required>
            </div>
            
            <div style="margin-top:15px;">
                <label for="resume">Résumé (pour PV) :</label><br>
                <textarea id="resume" name="resume" rows="4" maxlength="500"></textarea>
            </div>
            
            <button type="submit" style="margin-top:20px;">Soumettre le rapport</button>
        </form>
    <?php else: ?>
        <p class="success">✅ Rapport version <?= $rapport_actuel['version'] ?> validé par l'encadrant.</p>
    <?php endif; ?>

    <?php if ($rapport_actuel): ?>
        <h4 style="margin-top:30px;">Historique des rapports :</h4>
        <ul>
            <?php 
            $rapports = $db->query("SELECT * FROM rapports WHERE projet_id = $projet_id ORDER BY version DESC");
            while ($r = $rapports->fetch_assoc()): 
            ?>
                <li>Version <?= $r['version'] ?> - 
                    <?= $r['valide_encadrant'] ? '✅ Validé' : '⏳ En attente' ?> 
                    (<?= format_file_size($r['taille']) ?>)
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<?php
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB'];
    for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
    return round($bytes, 1) . ' ' . $units[$i];
}
?>