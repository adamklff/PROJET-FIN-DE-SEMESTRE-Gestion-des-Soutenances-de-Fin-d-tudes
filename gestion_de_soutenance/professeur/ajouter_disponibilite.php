<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['professeur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mes_disponibilites.php');
    exit;
}

$periode_id   = (int)($_POST['periode_id'] ?? 0);
$date_dispo   = $_POST['date_disponible'] ?? '';  // ← nouveau champ à ajouter dans le form
$debut        = $_POST['heure_debut'] ?? '';
$fin          = $_POST['heure_fin'] ?? '';

if ($periode_id <= 0 || empty($date_dispo) || empty($debut) || empty($fin)) {
    $_SESSION['error'] = "Données invalides.";
    header('Location: mes_disponibilites.php');
    exit;
}

$db = getDB();

if ($debut >= $fin) {
    $_SESSION['error'] = "L'heure de fin doit être postérieure à l'heure de début.";
    header('Location: mes_disponibilites.php');
    exit;
}

// Requête corrigée avec les vrais noms de colonnes
$query = "
    INSERT INTO disponibilites 
    (professeur_id, periode_id, date_disponible, heure_debut, heure_fin)
    VALUES (?, ?, ?, ?, ?)
";

$stmt = $db->prepare($query);

if ($stmt === false) {
    $_SESSION['error'] = "Erreur préparation requête : " . $db->error;
    header('Location: mes_disponibilites.php');
    exit;
}

// Vérifie doublon
$check = $db->prepare("
    SELECT id FROM disponibilites 
    WHERE professeur_id = ? AND periode_id = ? 
    AND date_disponible = ? AND heure_debut = ?
");
$check->bind_param("iiis", $_SESSION['user_id'], $periode_id, $date_dispo, $debut);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Ce créneau existe déjà.";
    header('Location: mes_disponibilites.php');
    exit;
}

// Insertion
$stmt->bind_param("iisss", $_SESSION['user_id'], $periode_id, $date_dispo, $debut, $fin);

if ($stmt->execute()) {
    $_SESSION['success'] = "Créneau ajouté avec succès !";
} else {
    $_SESSION['error'] = "Erreur lors de l'enregistrement : " . $stmt->error;
}

header('Location: mes_disponibilites.php');
exit;
?>