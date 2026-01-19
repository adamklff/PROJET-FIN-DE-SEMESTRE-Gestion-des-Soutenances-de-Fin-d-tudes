<?php
require_once 'config.php';

// Helper: Sanitize input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper: Check role access
function checkRole($allowedRoles) {
    $role = getUserRole();
    if (!in_array($role, $allowedRoles)) {
        die("Access denied.");
    }
}

function formatStatut($statut) {
    $map = [
        'inscrit'              => 'Inscrit (en attente d\'encadrant)',
        'encadrant_affecte'    => 'Encadrant affecté',
        'en_cours'             => 'Projet en cours',
        'rapport_soumis'       => 'Rapport soumis',
        'valide_encadrant'     => 'Rapport validé par l\'encadrant',
        'planifie'             => 'Soutenance planifiée',
        'soutenu'              => 'Soutenance terminée',
        'ajourne'              => 'Ajournement'
    ];
    return $map[$statut] ?? ucfirst(str_replace('_', ' ', $statut));
}

function getStatusColor($statut) {
    $colors = [
        'inscrit'           => 'color:#e67e22;',           // orange
        'encadrant_affecte' => 'color:#3498db;',           // bleu
        'en_cours'          => 'color:#2ecc71;',           // vert
        'rapport_soumis'    => 'color:#9b59b6;',           // violet
        'valide_encadrant'  => 'color:#27ae60;',           // vert foncé
        'planifie'          => 'color:#f1c40f;',           // jaune
        'soutenu'           => 'color:#2c3e50;',           // presque noir
        'ajourne'           => 'color:#c0392b;'            // rouge
    ];
    return $colors[$statut] ?? '';
}

// Afficher messages système
function showMessages() {
    if (isset($_SESSION['success'])) {
        echo '<p class="success" style="background:#d4edda; color:#155724; padding:12px; border:1px solid #c3e6cb; border-radius:4px; margin:15px 0;">' . 
             htmlspecialchars($_SESSION['success']) . '</p>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<p class="error" style="background:#f8d7da; color:#721c24; padding:12px; border:1px solid #f5c6cb; border-radius:4px; margin:15px 0;">' . 
             htmlspecialchars($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
}

/**
 * Checks if professor has declared availability for given date/time
 * Returns true if he has at least one slot that covers requested time
 */
function isProfessorAvailableOnDateTime($prof_id, $date, $heure_debut, $heure_fin, $db) {
    $query = "
        SELECT 1
        FROM disponibilites d
        JOIN periodes_disponibilite p ON d.periode_id = p.id
        WHERE d.professeur_id = ?
          AND d.date_disponible = ?
          AND d.heure_debut <= ?
          AND d.heure_fin   >= ?
          AND p.statut = 'en_cours'
          AND ? BETWEEN p.date_debut_soutenances AND p.date_fin_soutenances
        LIMIT 1
    ";

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        error_log("Erreur prepare isProfessorAvailableOnDateTime: " . $db->error);
        return false;
    }

    $stmt->bind_param("issss", $prof_id, $date, $heure_fin, $heure_debut, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function hasOverlappingDefense($prof_id, $date, $heure_debut, $heure_fin, $exclude_soutenance_id = null, $db) {
    $exclude = $exclude_soutenance_id ?? 0;

    $query = "
        SELECT s.id
        FROM jurys j
        JOIN soutenances s ON j.soutenance_id = s.id
        WHERE j.professeur_id = ?
          AND s.date_soutenance = ?
          AND s.id != ?
          AND (
              (s.heure_debut < ? AND s.heure_fin > ?) OR
              (s.heure_debut >= ? AND s.heure_debut < ?) OR
              (s.heure_fin > ? AND s.heure_fin <= ?)
          )
    ";

    $stmt = $db->prepare($query);
    if ($stmt === false) {
        error_log("Erreur prepare hasOverlappingDefense: " . $db->error);
        return false;
    }

    $stmt->bind_param("isissssss",
        $prof_id, $date, $exclude,
        $heure_fin, $heure_debut,
        $heure_debut, $heure_fin,
        $heure_debut, $heure_fin
    );

    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function calculerNoteFinaleEtMention($soutenance_id, $db) {
    // Récupère les notes
    $result = $db->query("
        SELECT note_attribuee, role_jury 
        FROM jurys 
        WHERE soutenance_id = $soutenance_id AND note_attribuee IS NOT NULL
    ");

    if (!$result) {
        error_log("Erreur requête notes : " . $db->error);
        return ['moyenne' => null, 'mention' => 'Erreur SQL'];
    }

    $notes = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($notes)) {
        error_log("Aucune note pour soutenance $soutenance_id");
        return ['moyenne' => null, 'mention' => 'Aucune note'];
    }

    // Calcul (tolérance 1 note pour test, change à >=2 en production)
    if (count($notes) === 1) {
        $moyenne = $notes[0]['note_attribuee'];
    } else {
        $total = 0;
        $poids_total = 0;

        foreach ($notes as $n) {
            if ($n['role_jury'] === 'president') {
                $total += $n['note_attribuee'] * 0.4;
                $poids_total += 0.4;
            } elseif ($n['role_jury'] === 'encadrant') {
                $total += $n['note_attribuee'] * 0.3;
                $poids_total += 0.3;
            } else {
                $total += $n['note_attribuee'] * 0.15;
                $poids_total += 0.15;
            }
        }

        if ($poids_total == 0) {
            $moyenne = 0;
        } else {
            $moyenne = $total / $poids_total;
        }
    }

    $mention = match(true) {
        $moyenne >= 18 => 'Excellent',
        $moyenne >= 16 => 'Très Bien',
        $moyenne >= 14 => 'Bien',
        $moyenne >= 12 => 'Assez Bien',
        default => 'Passable'
    };

    // Mise à jour soutenances
    $update_sout = $db->prepare("
        UPDATE soutenances 
        SET note_finale = ?, mention = ?, statut = 'terminee' 
        WHERE id = ?
    ");
    $update_sout->bind_param("dsi", $moyenne, $mention, $soutenance_id);
    $update_sout->execute();

    if ($update_sout->affected_rows === 0) {
        error_log("Aucune ligne mise à jour dans soutenances pour ID $soutenance_id");
    }

    // Synchronise statut dans projets
    $projet_query = $db->query("SELECT projet_id FROM soutenances WHERE id = $soutenance_id");
    if ($projet_query) {
        $row = $projet_query->fetch_assoc();
        $projet_id = $row['projet_id'] ?? null;

        if ($projet_id) {
            $db->query("UPDATE projets SET statut = 'soutenu' WHERE id = $projet_id");
        }
    }

    return ['moyenne' => round($moyenne, 2), 'mention' => $mention];
}

function sendEmail($to, $subject, $body) {
    require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings (use your Gmail or other SMTP)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';             // Gmail SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'benhabder@gmail.com';        // ← change to your email
        $mail->Password   = 'sowe ziso mhgn xful';          // ← Gmail App Password (not normal password!)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('yourgmail@gmail.com', 'Gestion Soutenances');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        return false;
    }
}
?>