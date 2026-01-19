<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'C:\xampp1\htdocs\gestion-soutenances\fpdf\fpdf.php'; // Adjust path to your FPDF file

requireLogin();

// For testing: use current user (student) or pass ?projet_id=XX
$projet_id = (int)($_GET['projet_id'] ?? 0);
if (!$projet_id) {
    // Use logged-in student's project (simplified)
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM projets WHERE etudiant_id = ? AND statut = 'planifie' LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $projet_id = $row['id'];
    }
}

if (!$projet_id) {
    die("Aucune soutenance planifiée pour vous.");
}

// Fetch all needed info
$db = getDB();
$sout = $db->query("
    SELECT s.date_soutenance, s.heure_debut, s.heure_fin, sa.nom AS salle,
           p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    JOIN salles sa ON s.salle_id = sa.id
    WHERE s.projet_id = $projet_id
")->fetch_assoc();

if (!$sout) {
    die("Soutenance non trouvée.");
}

// Jury members
$jury = $db->query("
    SELECT u.prenom, u.nom, j.role_jury
    FROM jurys j
    JOIN utilisateurs u ON j.professeur_id = u.id
    WHERE j.soutenance_id = (SELECT id FROM soutenances WHERE projet_id = $projet_id)
    ORDER BY FIELD(j.role_jury, 'president', 'encadrant', 'examinateur')
")->fetch_all(MYSQLI_ASSOC);

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'CONVOCATION A LA SOUTENANCE', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Monsieur/Madame ' . $sout['etudiant'], 0, 1);
$pdf->Ln(5);

$pdf->Cell(0, 8, 'Vous etes convoque(e) a la soutenance de votre Projet de Fin d\'Etudes :', 0, 1);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode($sout['titre']), 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Date : ' . date('d/m/Y', strtotime($sout['date_soutenance'])), 0, 1);
$pdf->Cell(0, 8, 'Heure : ' . substr($sout['heure_debut'], 0, 5) . ' - ' . substr($sout['heure_fin'], 0, 5), 0, 1);
$pdf->Cell(0, 8, 'Salle : ' . $sout['salle'], 0, 1);
$pdf->Ln(10);

// Jury
$pdf->Cell(0, 10, 'Composition du jury :', 0, 1);
foreach ($jury as $m) {
    $pdf->Cell(0, 8, '- ' . $m['prenom'] . ' ' . $m['nom'] . ' (' . ucfirst($m['role_jury']) . ')', 0, 1);
}

$pdf->Ln(20);
$pdf->Cell(0, 10, 'Cordialement,', 0, 1);
$pdf->Cell(0, 10, 'Le Coordinateur de Filiere', 0, 1);

// Output PDF
$pdf->Output('D', 'convocation_' . $projet_id . '.pdf'); // D = download
?>