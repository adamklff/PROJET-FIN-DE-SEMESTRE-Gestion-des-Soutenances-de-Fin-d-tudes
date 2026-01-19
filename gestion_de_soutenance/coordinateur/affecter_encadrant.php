<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['coordinateur']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projet_id    = (int)($_POST['projet_id'] ?? 0);
    $encadrant_id = (int)($_POST['encadrant_id'] ?? 0);

    if ($projet_id > 0 && $encadrant_id > 0) {
        $db = getDB();

        // Simple check: ne pas dépasser la charge max (facultatif pour l'instant)
        $stmt = $db->prepare("
            UPDATE projets 
            SET encadrant_id = ?, 
                statut = 'encadrant_affecte',
                date_affectation = NOW()
            WHERE id = ? 
            AND filiere_id = ? 
            AND encadrant_id IS NULL
        ");
        $stmt->bind_param("iii", $encadrant_id, $projet_id, $_SESSION['filiere_id']);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Optionnel : envoyer notification (pour plus tard)
            $_SESSION['message'] = "Encadrant affecté avec succès !";
        } else {
            $_SESSION['error'] = "Erreur lors de l'affectation.";
        }
    }
}

header('Location: projets_en_attente.php');
exit;
?>