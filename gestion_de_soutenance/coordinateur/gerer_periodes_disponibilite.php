<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['coordinateur']);

$db = getDB();
$annee = date('Y') . '-' . (date('Y') + 1); // 2025-2026 par exemple
$filiere_id = $_SESSION['filiere_id'];

$message = '';
$error = '';

// Récupérer la période actuelle ou la plus récente
$periode_actuelle = $db->query("
    SELECT * FROM periodes_disponibilite 
    WHERE filiere_id = $filiere_id 
    AND annee_universitaire = '$annee'
    ORDER BY date_debut_saisie DESC
    LIMIT 1
")->fetch_assoc();

// Création d'une nouvelle période
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'creer') {
    $debut_saisie = $_POST['date_debut_saisie'] ?? '';
    $fin_saisie   = $_POST['date_fin_saisie'] ?? '';
    $debut_sout   = $_POST['date_debut_soutenances'] ?? '';
    $fin_sout     = $_POST['date_fin_soutenances'] ?? '';

    if ($debut_saisie && $fin_saisie && $debut_saisie < $fin_saisie) {
        $stmt = $db->prepare("
            INSERT INTO periodes_disponibilite 
            (filiere_id, annee_universitaire, date_debut_saisie, date_fin_saisie, 
             date_debut_soutenances, date_fin_soutenances, statut)
            VALUES (?, ?, ?, ?, ?, ?, 'a_venir')
        ");
        $stmt->bind_param("isssss", 
            $filiere_id, $annee, $debut_saisie, $fin_saisie, $debut_sout, $fin_sout
        );
        
        if ($stmt->execute()) {
            $message = "Nouvelle période créée avec succès !";
            // Rafraîchir la période actuelle
            $periode_actuelle = $db->query("SELECT * FROM periodes_disponibilite WHERE id = " . $db->insert_id)->fetch_assoc();
        } else {
            $error = "Erreur lors de la création de la période.";
        }
    } else {
        $error = "Veuillez remplir correctement les dates (début < fin).";
    }
}

// Changer le statut (ouvrir / fermer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $periode_actuelle) {
    $nouveau_statut = '';
    if ($_POST['action'] === 'ouvrir') {
        $nouveau_statut = 'en_cours';
        $message = "Période ouverte : les professeurs peuvent maintenant saisir leurs disponibilités.";
    } elseif ($_POST['action'] === 'cloturer') {
        $nouveau_statut = 'cloturee';
        $message = "Période clôturée : plus de modifications possibles.";
    }

    if ($nouveau_statut) {
        $db->query("
            UPDATE periodes_disponibilite 
            SET statut = '$nouveau_statut' 
            WHERE id = {$periode_actuelle['id']}
        ");
        $periode_actuelle['statut'] = $nouveau_statut;
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div style="max-width:900px; margin:30px auto;">
    <h2>Gestion des périodes de disponibilités (<?= $annee ?>)</h2>

    <?php if ($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border:1px solid #c3e6cb; border-radius:4px; margin-bottom:20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border:1px solid #f5c6cb; border-radius:4px; margin-bottom:20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($periode_actuelle): ?>
        <div style="background:#f8f9fa; padding:20px; border:1px solid #dee2e6; border-radius:6px; margin-bottom:30px;">
            <h3>Période actuelle</h3>
            <p><strong>Statut :</strong> 
                <span style="font-weight:bold; <?= $periode_actuelle['statut'] === 'en_cours' ? 'color:#28a745;' : ($periode_actuelle['statut'] === 'cloturee' ? 'color:#dc3545;' : 'color:#6c757d;') ?>">
                    <?= $periode_actuelle['statut'] === 'a_venir' ? 'À venir' : ($periode_actuelle['statut'] === 'en_cours' ? 'En cours' : 'Clôturée') ?>
                </span>
            </p>
            <p><strong>Saisie des disponibilités :</strong> du 
                <strong><?= date('d/m/Y', strtotime($periode_actuelle['date_debut_saisie'])) ?></strong> 
                au <strong><?= date('d/m/Y', strtotime($periode_actuelle['date_fin_saisie'])) ?></strong></p>
            <?php if ($periode_actuelle['date_debut_soutenances']): ?>
            <p><strong>Période des soutenances prévue :</strong> du 
                <?= date('d/m/Y', strtotime($periode_actuelle['date_debut_soutenances'])) ?> 
                au <?= date('d/m/Y', strtotime($periode_actuelle['date_fin_soutenances'])) ?></p>
            <?php endif; ?>
        </div>

        <!-- Actions possibles -->
        <div style="margin:30px 0;">
            <?php if ($periode_actuelle['statut'] === 'a_venir'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="ouvrir">
                    <button type="submit" style="background:#28a745; color:white; padding:10px 25px; border:none; border-radius:4px; cursor:pointer;">
                        Ouvrir la période maintenant
                    </button>
                </form>
            <?php elseif ($periode_actuelle['statut'] === 'en_cours'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="cloturer">
                    <button type="submit" onclick="return confirm('Voulez-vous vraiment clôturer la période ? Les professeurs ne pourront plus modifier leurs disponibilités.');"
                            style="background:#dc3545; color:white; padding:10px 25px; border:none; border-radius:4px; cursor:pointer;">
                        Clôturer la période
                    </button>
                </form>
            <?php else: ?>
                <p style="color:#6c757d; font-style:italic;">
                    Cette période est déjà clôturée.
                </p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div style="background:#fff3cd; padding:20px; border:1px solid #ffeeba; border-radius:6px; margin-bottom:30px;">
            <p>Aucune période n'a encore été créée pour cette année universitaire.</p>
        </div>
    <?php endif; ?>

    <!-- Formulaire de création d'une nouvelle période -->
    <?php if (!$periode_actuelle || $periode_actuelle['statut'] === 'cloturee'): ?>
        <h3>Créer une nouvelle période</h3>
        <form method="post">
            <input type="hidden" name="action" value="creer">

            <div style="margin:15px 0;">
                <label>Date de début de saisie :</label><br>
                <input type="date" name="date_debut_saisie" required>
            </div>

            <div style="margin:15px 0;">
                <label>Date de fin de saisie :</label><br>
                <input type="date" name="date_fin_saisie" required>
            </div>

            <div style="margin:15px 0;">
                <label>Date de début des soutenances (facultatif) :</label><br>
                <input type="date" name="date_debut_soutenances">
            </div>

            <div style="margin:15px 0;">
                <label>Date de fin des soutenances (facultatif) :</label><br>
                <input type="date" name="date_fin_soutenances">
            </div>

            <button type="submit" style="padding:10px 25px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
                Créer la période
            </button>
        </form>
    <?php endif; ?>

    <p style="margin-top:40px;">
        <a href="../dashboard.php" class="secondary">← Retour au tableau de bord</a>
    </p>
</div>
</div>
<?php include '../includes/footer.php'; ?>