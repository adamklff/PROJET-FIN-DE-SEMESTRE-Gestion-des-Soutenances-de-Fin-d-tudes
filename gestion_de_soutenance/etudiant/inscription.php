<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['etudiant']); // Only students

$error = '';
$success = '';

$db = getDB();

// Get current user (student)
$etudiant_id = $_SESSION['user_id'];

// Check if student already has a project this year
$annee = date('Y') . '-' . (date('Y') + 1); // ex: 2025-2026

$check = $db->prepare("SELECT id FROM projets 
                       WHERE etudiant_id = ? 
                       AND annee_universitaire = ?");
$check->bind_param("is", $etudiant_id, $annee);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    $error = "Vous avez déjà inscrit un projet pour l'année universitaire $annee.";
}

// Get list of potential encadrants (professeurs of the same filiere)
$profs = $db->query("SELECT id, CONCAT(prenom, ' ', nom) AS nom_complet 
                     FROM utilisateurs 
                     WHERE role = 'professeur' 
                     AND filiere_id = " . (int)$_SESSION['filiere_id'] . "
                     AND actif = 1 
                     ORDER BY nom_complet");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $titre       = sanitize($_POST['titre'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $mots_cles   = sanitize($_POST['mots_cles'] ?? '');  // ex: "IA, Machine Learning, Python"
    $domaine     = sanitize($_POST['domaine'] ?? '');
    $type_projet = $_POST['type_projet'] ?? 'solo';
    $binome_email = trim($_POST['binome_email'] ?? '');

    $pref1 = !empty($_POST['pref1']) ? (int)$_POST['pref1'] : null;
    $pref2 = !empty($_POST['pref2']) ? (int)$_POST['pref2'] : null;
    $pref3 = !empty($_POST['pref3']) ? (int)$_POST['pref3'] : null;

    if (empty($titre) || empty($description) || empty($mots_cles)) {
        $error = "Les champs titre, description et mots-clés sont obligatoires.";
    } else {
        // For simplicity now: we store preferences as JSON
        $preferences = json_encode(array_filter([$pref1, $pref2, $pref3]));

        $stmt = $db->prepare("INSERT INTO projets 
            (titre, description, mots_cles, etudiant_id, filiere_id, annee_universitaire, statut, date_inscription)
            VALUES (?, ?, ?, ?, ?, ?, 'inscrit', NOW())");

        $stmt->bind_param("sssiss", 
            $titre, 
            $description, 
            $mots_cles,           // ← ici on passe directement la chaîne texte
            $etudiant_id, 
            $_SESSION['filiere_id'], 
            $annee
        );

        if ($stmt->execute()) {
            $projet_id = $db->insert_id;

            // Optional: save binôme (very basic version - just email for now)
            if ($type_projet === 'binome' && !empty($binome_email)) {
                // In real version you would search user by email and link by ID
                // Here we just store the email as text (temporary simplification)
                $db->query("UPDATE projets SET binome_email = '$binome_email' WHERE id = $projet_id");
            }

            $success = "Votre projet a été inscrit avec succès ! En attente d'affectation d'encadrant.";
        } else {
            $error = "Erreur lors de l'enregistrement : " . $db->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<main style="max-width:800px; margin:30px auto;">
    <div class="card">
    <h2>Inscription de votre Projet de Fin d'Études</h2>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
        <p><a href="../dashboard.php">← Retour au tableau de bord</a></p>
    <?php elseif ($existing): ?>
        <p class="info">Vous avez déjà un projet inscrit pour cette année.</p>
        <p><a href="../dashboard.php">← Retour au tableau de bord</a></p>
    <?php else: ?>
        <form method="POST" action="">
            <div>
                <label for="titre">Titre du projet * :</label><br>
                <input type="text" id="titre" name="titre" required>
            </div>

            <div>
                <label for="description">Description détaillée * :</label><br>
                <textarea id="description" name="description" rows="6" required></textarea>
            </div>

            <div>
                <label for="mots_cles">Mots-clés (séparés par des virgules) * :</label><br>
                <input type="text" id="mots_cles" name="mots_cles" placeholder="ex: IA, Machine Learning, Python" required>
            </div>

            <div>
                <label for="domaine">Domaine / Thématique :</label><br>
                <input type="text" id="domaine" name="domaine" placeholder="ex: Intelligence Artificielle, Développement Web">
            </div>

            <div>
                <label>Type de projet :</label><br>
                <label><input type="radio" name="type_projet" value="solo" checked> Solo</label><br>
                <label><input type="radio" name="type_projet" value="binome"> En binôme</label>
            </div>

            <div id="binome_field" style="display:none; margin-top:10px;">
                <label for="binome_email">Email de votre binôme :</label><br>
                <input type="email" id="binome_email" name="binome_email" placeholder="exemple@etu.uae.ac.ma">
            </div>

            <div style="margin-top:20px;">
                <label>Préférences d'encadrants (facultatif) :</label><br>
                <select name="pref1">
                    <option value="">-- Aucun --</option>
                    <?php while($prof = $profs->fetch_assoc()): ?>
                        <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nom_complet']) ?></option>
                    <?php endwhile; ?>
                </select><br><br>

                <select name="pref2">
                    <option value="">-- Aucun --</option>
                    <?php $profs->data_seek(0); while($prof = $profs->fetch_assoc()): ?>
                        <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nom_complet']) ?></option>
                    <?php endwhile; ?>
                </select><br><br>

                <select name="pref3">
                    <option value="">-- Aucun --</option>
                    <?php $profs->data_seek(0); while($prof = $profs->fetch_assoc()): ?>
                        <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nom_complet']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" style="margin-top:25px; padding:12px 30px;">Soumettre mon projet</button>
        </form>
    <?php endif; ?>
    </div>
</main>

<script>
// Very simple show/hide for binôme field (we said no JS heavy, but this is minimal)
document.querySelectorAll('input[name="type_projet"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('binome_field').style.display = 
            (this.value === 'binome') ? 'block' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>