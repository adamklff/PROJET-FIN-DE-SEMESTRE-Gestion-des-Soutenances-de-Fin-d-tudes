<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['coordinateur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1);
$filiere_id = $_SESSION['filiere_id'];

$success = '';
$error = '';

// Get projects ready for planning (encadrant affecté or rapport validé)
$projets = $db->query("
    SELECT p.id, p.titre, 
           CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           CONCAT(enc.prenom, ' ', enc.nom) AS encadrant
    FROM projets p
    JOIN utilisateurs e ON p.etudiant_id = e.id
    JOIN utilisateurs enc ON p.encadrant_id = enc.id
    WHERE p.filiere_id = $filiere_id
    AND p.annee_universitaire = '$annee'
    AND p.statut IN ('encadrant_affecte', 'valide_encadrant')
    AND p.id NOT IN (SELECT projet_id FROM soutenances)
    ORDER BY p.titre
")->fetch_all(MYSQLI_ASSOC);

// Get available rooms
$salles = $db->query("
    SELECT id, nom, capacite 
    FROM salles 
    ORDER BY nom
")->fetch_all(MYSQLI_ASSOC);

// === Process form submission ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['planifier'])) {
    $projet_id     = (int)($_POST['projet_id'] ?? 0);
    $date          = $_POST['date_soutenance'] ?? '';
    $heure_debut   = $_POST['heure_debut'] ?? '';
    $heure_fin     = $_POST['heure_fin'] ?? '';
    $salle_id      = (int)($_POST['salle_id'] ?? 0);

    if (!$projet_id || !$date || !$heure_debut || !$heure_fin || !$salle_id) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $conflict = false;

        // 1. Vérifie salle libre
        $stmt = $db->prepare("
            SELECT id FROM soutenances 
            WHERE salle_id = ? 
            AND date_soutenance = ?
            AND (
                (heure_debut <= ? AND heure_fin > ?) OR
                (heure_debut < ? AND heure_fin >= ?) OR
                (heure_debut >= ? AND heure_fin <= ?)
            )
        ");
        $stmt->bind_param("isssssss", $salle_id, $date, $heure_fin, $heure_debut, $heure_fin, $heure_debut, $heure_debut, $heure_fin);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $conflict = true;
            $error = "La salle est déjà occupée à ce créneau.";
        }

        // 2. Vérifie encadrant libre (seulement l'encadrant pour l'instant)
        $projet = $db->query("SELECT encadrant_id FROM projets WHERE id = $projet_id")->fetch_assoc();
        $encadrant_id = $projet['encadrant_id'];

        $stmt = $db->prepare("
            SELECT s.id FROM soutenances s
            JOIN jurys j ON s.id = j.soutenance_id
            WHERE j.professeur_id = ?
            AND s.date_soutenance = ?
            AND (
                (s.heure_debut <= ? AND s.heure_fin > ?) OR
                (s.heure_debut < ? AND s.heure_fin >= ?) OR
                (s.heure_debut >= ? AND s.heure_fin <= ?)
            )
        ");
        $stmt->bind_param("isssssss", $encadrant_id, $date, $heure_fin, $heure_debut, $heure_fin, $heure_debut, $heure_debut, $heure_fin);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $conflict = true;
            $error = "L'encadrant est déjà pris à ce créneau.";
        }

        // 3. Si pas de conflit, insère la soutenance
        if (!$conflict) {
            $stmt = $db->prepare("
                INSERT INTO soutenances 
                (projet_id, salle_id, date_soutenance, heure_debut, heure_fin, statut)
                VALUES (?, ?, ?, ?, ?, 'planifiee')
            ");
            $stmt->bind_param("iisss", $projet_id, $salle_id, $date, $heure_debut, $heure_fin);

            if ($stmt->execute()) {
                $soutenance_id = $db->insert_id;

                // Ajoute l'encadrant au jury automatiquement
                $db->query("
                    INSERT INTO jurys (soutenance_id, professeur_id, role_jury)
                    VALUES ($soutenance_id, $encadrant_id, 'encadrant')
                ");

                $db->query("UPDATE projets SET statut = 'planifie' WHERE id = $projet_id");

                $success = "Soutenance planifiée avec succès !";
            } else {
                $error = "Erreur lors de la planification : " . $db->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div style="max-width:1000px; margin:30px auto;">
    <h2>Planification manuelle des soutenances</h2>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border:1px solid #c3e6cb; border-radius:4px; margin-bottom:20px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border:1px solid #f5c6cb; border-radius:4px; margin-bottom:20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($projets)): ?>
        <p style="font-style:italic; color:#666;">
            Aucun projet prêt pour la planification (ou tous déjà planifiés).
        </p>
    <?php else: ?>
        <form method="post">
            <div style="margin-bottom:25px;">
                <label><strong>Projet à planifier :</strong></label><br>
                <select name="projet_id" required style="padding:8px; width:100%; max-width:500px;">
                    <option value="">-- Sélectionner un projet --</option>
                    <?php foreach ($projets as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['titre']) ?> 
                            (<?= htmlspecialchars($p['etudiant']) ?> – Encadrant: <?= htmlspecialchars($p['encadrant']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px;">
                <div>
                    <label><strong>Date de la soutenance :</strong></label><br>
                    <input type="date" name="date_soutenance" required style="padding:8px; width:100%;">
                </div>

                <div>
                    <label><strong>Salle :</strong></label><br>
                    <select name="salle_id" required style="padding:8px; width:100%;">
                        <option value="">-- Choisir une salle --</option>
                        <?php foreach ($salles as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['nom']) ?> (cap. <?= $s['capacite'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px;">
                <div>
                    <label><strong>Heure de début :</strong></label><br>
                    <input type="time" name="heure_debut" required step="900" style="padding:8px; width:100%;">
                </div>

                <div>
                    <label><strong>Heure de fin :</strong></label><br>
                    <input type="time" name="heure_fin" required step="900" style="padding:8px; width:100%;">
                </div>
            </div>

            <button type="submit" name="planifier"
                    style="padding:12px 30px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; font-size:1.1em;">
                Planifier cette soutenance
            </button>
        </form>
    <?php endif; ?>

    <p style="margin-top:40px;">
        <a href="../dashboard.php" class="secondary">← Retour au tableau de bord</a>
    </p>
</div>
</div>
<?php include '../includes/footer.php'; ?>