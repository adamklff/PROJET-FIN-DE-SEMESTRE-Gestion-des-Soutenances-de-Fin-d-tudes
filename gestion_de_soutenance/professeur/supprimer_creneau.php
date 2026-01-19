<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

$slot_id = (int)($_POST['slot_id'] ?? 0);

if ($slot_id > 0) {
    $db = getDB();
    $stmt = $db->prepare("
        DELETE FROM disponibilites 
        WHERE id = ? AND professeur_id = ?
    ");
    $stmt->bind_param("ii", $slot_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Créneau supprimé.";
    } else {
        $_SESSION['error'] = "Créneau non trouvé ou accès refusé.";
    }
}

header('Location: mes_disponibilites.php');
exit;