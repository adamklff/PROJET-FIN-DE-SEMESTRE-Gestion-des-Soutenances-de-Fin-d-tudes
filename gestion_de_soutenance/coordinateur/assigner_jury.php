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

// Get all planned but not yet fully assigned defenses
$soutenances = $db->query("
    SELECT s.id, s.date_soutenance, s.heure_debut, s.heure_fin,
           p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant,
           CONCAT(enc.prenom, ' ', enc.nom) AS encadrant,
           enc.id AS encadrant_id,
           (SELECT COUNT(*) FROM jurys WHERE soutenance_id = s.id) AS nb_membres
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    JOIN utilisateurs enc ON p.encadrant_id = enc.id
    WHERE p.filiere_id = $filiere_id
    AND p.annee_universitaire = '$annee'
    AND s.statut = 'planifiee'
    ORDER BY s.date_soutenance, s.heure_debut
")->fetch_all(MYSQLI_ASSOC);

// Get all active professors in the filiere
$professeurs = $db->query("
    SELECT id, CONCAT(prenom, ' ', nom) AS nom_complet
    FROM utilisateurs 
    WHERE role = 'professeur' 
    AND filiere_id = $filiere_id 
    AND actif = 1
    ORDER BY nom_complet
")->fetch_all(MYSQLI_ASSOC);

// === Process jury assignment ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assigner_jury'])) {
    $soutenance_id = (int)($_POST['soutenance_id'] ?? 0);
    $president_id  = (int)($_POST['president_id'] ?? 0);
    $examinateurs  = $_POST['examinateurs'] ?? []; // array of professor ids

    if ($soutenance_id <= 0 || $president_id <= 0 || empty($examinateurs)) {
        $error = "Veuillez sélectionner un président et au moins un examinateur.";
    } else {
        // Get current soutenance info
        $sout = $db->query("
            SELECT s.*, p.encadrant_id 
            FROM soutenances s 
            JOIN projets p ON s.projet_id = p.id 
            WHERE s.id = $soutenance_id
        ")->fetch_assoc();

        if (!$sout) {
            $error = "Soutenance introuvable.";
        } else {
            $encadrant_id = $sout['encadrant_id'];

            // Basic validation rules
            if ($president_id == $encadrant_id) {
                $error = "L'encadrant ne peut pas être président du jury.";
            } elseif (in_array($encadrant_id, $examinateurs) && count($examinateurs) < 1) {
                $error = "L'encadrant est déjà inclus automatiquement.";
            } elseif (count($examinateurs) < 1) {
                $error = "Il faut au moins un examinateur supplémentaire.";
            } else {
                // Start transaction
                $db->begin_transaction();

                try {
                    // 1. Clear existing jury (except encadrant if already there)
                    $db->query("DELETE FROM jurys WHERE soutenance_id = $soutenance_id AND role_jury != 'encadrant'");

                    // 2. Add president
                    $db->query("
                        INSERT INTO jurys (soutenance_id, professeur_id, role_jury)
                        VALUES ($soutenance_id, $president_id, 'president')
                    ");

                    // 3. Add examinateurs
                    foreach ($examinateurs as $prof_id) {
                        $prof_id = (int)$prof_id;
                        if ($prof_id != $president_id && $prof_id != $encadrant_id) {
                            $db->query("
                                INSERT INTO jurys (soutenance_id, professeur_id, role_jury)
                                VALUES ($soutenance_id, $prof_id, 'examinateur')
                            ");
                        }
                    }

                    // 4. Make sure encadrant is in jury (role = 'encadrant')
                    $db->query("
                        INSERT IGNORE INTO jurys (soutenance_id, professeur_id, role_jury)
                        VALUES ($soutenance_id, $encadrant_id, 'encadrant')
                    ");

                    // 5. Basic conflict check for jury members
                    $jury_members = array_merge([$president_id], $examinateurs, [$encadrant_id]);
                    $has_conflict = false;
                    foreach ($jury_members as $prof_id) {
                        if (hasOverlappingDefense($prof_id, $sout['date_soutenance'], $sout['heure_debut'], $sout['heure_fin'], $soutenance_id, $db)) {
                            $has_conflict = true;
                            $error = "Conflit détecté : le professeur ID $prof_id est déjà pris à un autre jury à la même heure.";
                            break;
                        }
                    }

                    if ($has_conflict) {
                        throw new Exception($error);
                    }
                    $all_jury_ids = array_unique(array_merge(
                        [$sout['encadrant_id']],
                        [$president_id],
                        $examinateurs
                    ));

                    if (!isProfessorAvailableOnDateTime($prof_id, $date, $debut, $fin, $db)) {
                        $warning = "Attention : le professeur n'a pas déclaré être disponible ce jour-là.";
                        // → show as yellow warning box
                        // → but still allow saving
                    }

                    foreach ($all_jury_ids as $prof_id) {
                        if (!isProfessorAvailableOnDateTime($prof_id, $sout['date_soutenance'], $sout['heure_debut'], $sout['heure_fin'], $db)) {
                            $error = "Au moins un membre du jury (ID $prof_id) n'est pas disponible à ce créneau.";
                            throw new Exception($error); // or just set $error and break
                        }
                    }
                    // If no conflict → continue with commit
                    $db->commit();
                    $success = "Jury assigné avec succès !";
                    $jury_members = array_unique($jury_members);

                    foreach ($jury_members as $prof_id) {
                        $conflict = $db->query("
                            SELECT s.id 
                            FROM soutenances s
                            JOIN jurys j ON s.id = j.soutenance_id
                            WHERE j.professeur_id = $prof_id
                            AND s.id != $soutenance_id
                            AND s.date_soutenance = '{$sout['date_soutenance']}'
                            AND (
                                (s.heure_debut <= '{$sout['heure_fin']}' AND s.heure_fin >= '{$sout['heure_debut']}')
                            )
                        ")->num_rows;

                        if ($conflict > 0) {
                            throw new Exception("Conflit d'horaire détecté pour un membre du jury.");
                        }
                    }

                    $db->commit();
                    $success = "Jury assigné avec succès !";
                } catch (Exception $e) {
                    $db->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div style="max-width:1100px; margin:30px auto;">
    <h2>Constitution manuelle des jurys</h2>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:4px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; margin-bottom:20px; border-radius:4px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($soutenances)): ?>
        <p style="font-style:italic; color:#666;">
            Aucune soutenance planifiée prête pour la constitution du jury.
        </p>
    <?php else: ?>
        <?php foreach ($soutenances as $s): ?>
            <div style="border:1px solid #ddd; padding:20px; margin-bottom:30px; border-radius:6px; background:#f9f9f9;">
                <h3><?= htmlspecialchars($s['titre']) ?></h3>
                <p>
                    <strong>Étudiant :</strong> <?= htmlspecialchars($s['etudiant']) ?><br>
                    <strong>Encadrant :</strong> <?= htmlspecialchars($s['encadrant']) ?><br>
                    <strong>Date :</strong> <?= date('d/m/Y', strtotime($s['date_soutenance'])) ?><br>
                    <strong>Heure :</strong> <?= $s['heure_debut'] ?> – <?= $s['heure_fin'] ?><br>
                    <strong>Membres actuels :</strong> <?= $s['nb_membres'] ?>
                </p>

                <form method="post">
                    <input type="hidden" name="soutenance_id" value="<?= $s['id'] ?>">

                    <div style="margin:20px 0;">
                        <label><strong>Président du jury :</strong> (ne peut pas être l'encadrant)</label><br>
                        <select name="president_id" required style="padding:8px; width:100%; max-width:400px;">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($professeurs as $p): ?>
                                <?php if ($p['id'] != $s['encadrant_id']): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['nom_complet']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin:20px 0;">
                        <label><strong>Examinateurs :</strong> (au moins 1)</label><br>
                        <select name="examinateurs[]" multiple size="6" required style="padding:8px; width:100%; max-width:400px;">
                            <?php foreach ($professeurs as $p): ?>
                                <?php if ($p['id'] != $s['encadrant_id']): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['nom_complet']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small style="display:block; margin-top:5px; color:#666;">
                            Maintenez Ctrl (Windows) / Cmd (Mac) pour sélectionner plusieurs
                        </small>
                    </div>

                    <button type="submit" name="assigner_jury"
                            style="padding:10px 25px; background:#6f42c1; color:white; border:none; border-radius:4px; cursor:pointer;">
                        Valider la composition du jury
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p style="margin-top:40px;">
        <a href="../dashboard.php" class="secondary">← Retour au tableau de bord</a>
    </p>
</div>
</div>
<?php include '../includes/footer.php'; ?>