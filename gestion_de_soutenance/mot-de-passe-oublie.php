<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Veuillez entrer votre email.";
    } else {
        $db = getDB();  // ← IMPORTANT : on définit $db ICI

        $stmt = $db->prepare("SELECT id, prenom FROM utilisateurs WHERE email = ? AND actif = 1");
        if ($stmt === false) {
            $error = "Erreur technique (préparation requête).";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Upsert token
                $stmt_token = $db->prepare("
                    INSERT INTO password_resets (email, token, expires_at) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                ");
                $stmt_token->bind_param("sssss", $email, $token, $expires, $token, $expires);
                $stmt_token->execute();

                $reset_link = "http://localhost/gestion-soutenances/reinitialiser-mot-de-passe.php?token=$token";

                $subject = "Réinitialisation de votre mot de passe";
                $body = "
                    <h2>Bonjour {$user['prenom']},</h2>
                    <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                    <p>Cliquez ici pour choisir un nouveau mot de passe :</p>
                    <p><a href='$reset_link' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expire dans 1 heure.</p>
                    <p>Si ce n'est pas vous, ignorez cet email.</p>
                    <p>Cordialement,<br>Gestion des Soutenances</p>
                ";

                if (sendEmail($email, $subject, $body)) {
                    $success = "Un email de réinitialisation a été envoyé à <strong>$email</strong>.<br>Vérifiez votre boîte (et spams).";
                } else {
                    $error = "Erreur lors de l'envoi de l'email. Contactez l'administrateur.";
                }
            } else {
                $error = "Aucun compte actif trouvé avec cet email.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="card">
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3 class="text-center mb-4">Mot de passe oublié ?</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Votre adresse email</label>
                        <input type="email" name="email" id="email" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Envoyer le lien de réinitialisation</button>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php">Retour à la connexion</a>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>