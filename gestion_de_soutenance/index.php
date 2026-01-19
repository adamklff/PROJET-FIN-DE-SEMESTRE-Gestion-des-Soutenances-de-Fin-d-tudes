<?php
session_start();

// Si déjà connecté → redirection immédiate
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Vérifie cookie "Se souvenir de moi"
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $db->prepare("
        SELECT u.id, u.nom, u.prenom, u.email, u.role, u.filiere_id
        FROM utilisateurs u
        JOIN remember_tokens t ON u.id = t.user_id
        WHERE t.token = ? 
          AND t.expires_at > NOW()
        LIMIT 1
    ");

    if ($stmt === false) {
        error_log("Erreur prepare auto-login : " . $db->error);
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Auto-login réussi
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['nom']        = $user['nom'];
            $_SESSION['prenom']     = $user['prenom'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['filiere_id'] = $user['filiere_id'];

            // Optionnel: rafraîchir le cookie pour prolonger 30 jours
            setcookie('remember_token', $token, time() + (30*24*60*60), '/', '', false, true);

            header("Location: dashboard.php");
            exit;
        } else {
            error_log("Token non trouvé ou expiré : $token");
            // Supprime le cookie invalide
            setcookie('remember_token', '', time() - 3600, '/');
        }
        $stmt->close();
    }
}

// 2. Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, nom, prenom, email, mot_de_passe, role, filiere_id 
            FROM utilisateurs 
            WHERE email = ? AND actif = 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();  // ← NO argument here!
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['nom']        = $user['nom'];
                $_SESSION['prenom']     = $user['prenom'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['filiere_id'] = $user['filiere_id'];

                // "Se souvenir de moi"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

                    // Supprime anciens tokens
                    $delete_stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                    if ($delete_stmt) {
                        $delete_stmt->bind_param("i", $user['id']);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                    } else {
                        error_log("Erreur prepare DELETE remember_tokens : " . $db->error);
                    }

                    // Insère nouveau token
                    $insert_stmt = $db->prepare("
                        INSERT INTO remember_tokens (user_id, token, expires_at) 
                        VALUES (?, ?, ?)
                    ");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("iss", $user['id'], $token, $expires);
                        $insert_stmt->execute();
                        $insert_stmt->close();
                    } else {
                        error_log("Erreur prepare INSERT remember_tokens : " . $db->error);
                    }

                    // Cookie sécurisé (30 jours)
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Email non trouvé ou compte inactif.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<main style="max-width:500px; margin:40px auto;">
    <div class="card">
    <h2>Connexion</h2>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="mb-3 form-check remember-me" style="display:flex">
            <input type="checkbox" name="remember" id="remember" style="height:15px; width:15px; margin-right:15px" >
            <label for="remember" class="form-check-label" style="margin-top:-25px">Se souvenir de moi</label>
        </div>

        <button type="submit" name="login" class="btn btn-primary w-100">Se connecter</button>

        <div class="mt-3 text-center">
            <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
        </div>
    </form>

    <p style="margin-top:20px; font-size:0.9em; color:#666;">
        Pour tester :<br>
        Étudiant → etudiant@test.com / password<br>
        Professeur → prof@test.com / password<br>
        Coordinateur → coord@test.com / password<br>
        Directeur → dir@test.com / password<br>
        Assistante → assi@test.com / password
    </p>
    </div>
</main>

<?php include 'includes/footer.php'; ?>