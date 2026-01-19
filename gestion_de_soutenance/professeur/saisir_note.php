<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$db = getDB();
$soutenance_id = (int)($_GET['soutenance_id'] ?? 0);

if ($soutenance_id <= 0) {
    die("<div class='error card'>Soutenance non spécifiée.</div>");
}

// Fetch jury entry for this professor
$stmt = $db->prepare("
    SELECT j.id AS jury_id, j.role_jury, j.note_attribuee, j.appreciation, j.presence_confirmee,
           s.date_soutenance, p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM jurys j
    JOIN soutenances s ON j.soutenance_id = s.id
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE j.soutenance_id = ? AND j.professeur_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $soutenance_id, $_SESSION['user_id']);
$stmt->execute();
$jury = $stmt->get_result()->fetch_assoc();

if (!$jury) {
    die("<div class='error card'>Vous n'êtes pas membre du jury pour cette soutenance.</div>");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = floatval($_POST['note'] ?? 0);
    $appreciation = trim($_POST['appreciation'] ?? '');
    $presence = isset($_POST['presence']) ? 1 : 0;

    if ($note < 0 || $note > 20) {
        $error = "La note doit être entre 0 et 20.";
    } else {
        $stmt = $db->prepare("
            UPDATE jurys 
            SET note_attribuee = ?, appreciation = ?, presence_confirmee = ?, date_notation = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("dsii", $note, $appreciation, $presence, $jury['jury_id']);

        if ($stmt->execute()) {
            $success = "Votre note a été enregistrée !";

            // Vérifie si TOUTES les notes sont maintenant saisies
            $check = $db->query("
                SELECT COUNT(*) AS total, 
                    COUNT(note_attribuee) AS saisies
                FROM jurys 
                WHERE soutenance_id = $soutenance_id
            ")->fetch_assoc();

            // Si TOUTES les notes sont présentes (saisies == total) et au moins 2 jurys
            if ($check['saisies'] == $check['total'] && $check['total'] >= 2) {
                $result = calculerNoteFinaleEtMention($soutenance_id, $db);
                if ($result['moyenne'] !== null) {
                    $success .= "<br><strong>Toutes les notes sont saisies ! Note finale calculée : {$result['moyenne']}/20 – {$result['mention']}</strong>";
                }
            } else {
                $reste = $check['total'] - $check['saisies'];
                $success .= "<br><em>En attente des $reste note(s) restante(s) pour calculer la note finale.</em>";
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <h2>Saisie de votre note – Soutenance</h2>

    <p><strong>Étudiant :</strong> <?= htmlspecialchars($jury['etudiant']) ?></p>
    <p><strong>Projet :</strong> <?= htmlspecialchars($jury['titre']) ?></p>
    <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($jury['date_soutenance'])) ?></p>
    <p><strong>Votre rôle :</strong> <?= ucfirst($jury['role_jury']) ?></p>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Note sur 20 :</label>
        <input type="number" name="note" step="0.25" min="0" max="20" 
               value="<?= htmlspecialchars($jury['note_attribuee'] ?? '') ?>" required>

        <label>Appréciation / Commentaires :</label>
        <textarea name="appreciation" rows="5"><?= htmlspecialchars($jury['appreciation'] ?? '') ?></textarea>

        <label style="margin-top:1rem; display:block;">
            <input type="checkbox" name="presence" <?= $jury['presence_confirmee'] ? 'checked' : '' ?>>
            J'ai assisté à la soutenance
        </label>

        <div style="margin-top:2rem;">
            <button type="submit" class="btn btn-primary">Enregistrer ma note</button>
            <a href="mes_projets.php" class="btn btn-secondary" style="margin-left:1rem;">Retour</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>