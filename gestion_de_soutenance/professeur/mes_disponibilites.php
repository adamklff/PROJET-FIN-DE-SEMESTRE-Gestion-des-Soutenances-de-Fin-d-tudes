<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$db = getDB();

// Get current or active period for the filiere
$annee = date('Y') . '-' . (date('Y') + 1);

$periode = $db->query("
    SELECT * FROM periodes_disponibilite 
    WHERE filiere_id = {$_SESSION['filiere_id']} 
    AND annee_universitaire = '$annee'
    AND statut = 'en_cours'
    LIMIT 1
")->fetch_assoc();

if (!$periode) {
    $message = "Aucune période de saisie des disponibilités n'est actuellement ouverte pour votre filière.";
}

// Get already entered slots for this professor in this period
$slots = [];

if ($periode) {
    $query = "
        SELECT id, date_disponible, heure_debut, heure_fin
        FROM disponibilites
        WHERE professeur_id = ? AND periode_id = ?
        ORDER BY date_disponible, heure_debut
    ";

    $stmt = $db->prepare($query);

    if ($stmt === false) {
        echo "<div class='error' style='background:#fee2e2; padding:1.5rem; border-radius:10px; margin:1.5rem 0; border-left:6px solid #e53e3e;'>
                <strong>Erreur SQL :</strong> " . htmlspecialchars($db->error) . "<br>
                <strong>Requête :</strong> " . htmlspecialchars($query) . "
              </div>";
        $slots = [];
    } else {
        $stmt->bind_param("ii", $_SESSION['user_id'], $periode['id']);
        $stmt->execute();
        $slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$jours = [1=>'Lundi', 2=>'Mardi', 3=>'Mercredi', 4=>'Jeudi', 5=>'Vendredi', 6=>'Samedi', 7=>'Dimanche'];
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div style="max-width:900px; margin:30px auto;">
    <h2>Mes disponibilités pour les jurys (<?= $annee ?>)</h2>

    <?php if (isset($message)): ?>
        <div style="background:#fff3cd; color:#856404; padding:15px; border:1px solid #ffeeba; border-radius:4px; margin-bottom:25px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php elseif ($periode): ?>
        <div style="background:#e8f5e9; padding:15px; margin-bottom:25px; border-radius:4px;">
            Période de saisie ouverte du <strong><?= date('d/m/Y', strtotime($periode['date_debut_saisie'])) ?></strong> 
            au <strong><?= date('d/m/Y', strtotime($periode['date_fin_saisie'])) ?></strong>
        </div>

        <!-- Current slots -->
        <?php if ($slots): ?>
            <h3>Vos créneaux enregistrés</h3>
            <table style="width:100%; border-collapse:collapse; margin-bottom:30px;">
                <thead>
                    <tr style="background:#f2f2f2;">
                        <th style="padding:10px; border:1px solid #ddd;">Date</th>
                        <th style="padding:10px; border:1px solid #ddd;">De</th>
                        <th style="padding:10px; border:1px solid #ddd;">À</th>
                        <th style="padding:10px; border:1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td style="padding:10px; border:1px solid #ddd;">
                                <?= date('d/m/Y', strtotime($slot['date_disponible'])) ?>
                            </td>
                            <td style="padding:10px; border:1px solid #ddd;">
                                <?= substr($slot['heure_debut'], 0, 5) ?>
                            </td>
                            <td style="padding:10px; border:1px solid #ddd;">
                                <?= substr($slot['heure_fin'], 0, 5) ?>
                            </td>
                            <td style="text-align:center;">
                                <form method="post" action="supprimer_creneau.php" style="display:inline;">
                                    <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                    <button type="submit" onclick="return confirm('Supprimer ce créneau ?');"
                                            class="btn btn-success">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#666; font-style:italic;">Aucun créneau enregistré pour le moment.</p>
        <?php endif; ?>

        <!-- Add new slot form -->
        <h3>Ajouter un nouveau créneau récurrent</h3>
        <form method="post" action="ajouter_disponibilite.php">
            <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>">

        <!-- Remplace le select jour par un input date -->
        <div style="margin:15px 0;">
            <label>Date disponible :</label><br>
            <input type="date" name="date_disponible" required>
        </div>

        <div style="margin:15px 0;">
            <label>Heure de début :</label><br>
            <input type="time" name="heure_debut" required step="1800">
        </div>

        <div style="margin:15px 0;">
            <label>Heure de fin :</label><br>
            <input type="time" name="heure_fin" required step="1800">
        </div>

            <button type="submit" style="padding:10px 25px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">
                Ajouter ce créneau
            </button>
        </form>

        <p style="margin-top:20px; color:#666; font-size:0.95em;">
            Note : Chaque créneau est **récurrent toutes les semaines** pendant la période de soutenances.
        </p>

    <?php endif; ?>

    <p style="margin-top:40px;">
        <a href="../dashboard.php" class="secondary">← Retour au tableau de bord</a>
    </p>
</div>
</div>
<?php include '../includes/footer.php'; ?>