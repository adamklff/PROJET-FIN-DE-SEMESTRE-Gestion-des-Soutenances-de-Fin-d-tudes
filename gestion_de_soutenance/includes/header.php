<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Soutenances</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>Gestion des Soutenances de Fin d'Études</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav>
            <a href="../dashboard.php">Tableau de Bord</a>
            <!-- Role-based links added later -->
            <a href="../logout.php">Déconnexion</a>
        </nav>
    <?php endif; ?>
</header>