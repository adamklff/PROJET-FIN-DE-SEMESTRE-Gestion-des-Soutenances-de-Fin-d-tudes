<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$password = getenv('DEMO_PASSWORD');
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Set DEMO_PASSWORD to at least 12 characters.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$hash = password_hash($password, PASSWORD_DEFAULT);
$roles = ['etudiant', 'professeur', 'coordinateur', 'directeur', 'assistante'];
$db->begin_transaction();
try {
    $stmt = $db->prepare(
        "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, filiere_id)
         VALUES ('Demo', ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE email = utilisateurs.email"
    );
    foreach ($roles as $role) {
        $name = ucfirst($role);
        $email = "$role@example.test";
        $stmt->bind_param('ssss', $name, $email, $hash, $role);
        $stmt->execute();
    }
    $db->query("INSERT INTO salles (nom, capacite) VALUES ('Salle Demo', 20)
                ON DUPLICATE KEY UPDATE nom = salles.nom");
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    fwrite(STDERR, "Unable to seed the demo database.\n");
    exit(1);
}

echo "Demo accounts: " . implode(', ', array_map(fn($role) => "$role@example.test", $roles)) . "\n";
