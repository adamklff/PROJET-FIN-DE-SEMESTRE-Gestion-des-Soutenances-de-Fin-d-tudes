<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error   = '';
$success = '';
$token   = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Lien de réinitialisation invalide ou manquant.";
} else {
    $db = getDB();

    // Vérifie le token et l'expiration
    $stmt = $db->prepare("
        SELECT email, expires_at 
        FROM password_resets 
        WHERE token = ? AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if (!$reset) {
        $error = "Ce lien est invalide ou a expiré. Veuillez demander un nouveau lien.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif ($password !== $password_confirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Mise à jour du mot de passe
            $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $reset['email']);
            $stmt->execute();

            // Supprime le token utilisé
            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $success = "Votre mot de passe a été réinitialisé avec succès !<br>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="card">
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3 class="text-center mb-4">Réinitialiser votre mot de passe</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-primary">Se connecter maintenant</a>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                    <div class="text-center mt-3">
                        <a href="mot-de-passe-oublie.php" class="btn btn-secondary">Demander un nouveau lien</a>
                    </div>
                <?php else: ?>
                    <!-- Formulaire de réinitialisation -->
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Changer le mot de passe</button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="index.php">Retour à la connexion</a>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>