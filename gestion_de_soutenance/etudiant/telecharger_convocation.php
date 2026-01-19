<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../fpdf/fpdf.php'; // Vérifie que fpdf.php est dans ce dossier ou ajuste le chemin

requireLogin();
checkRole(['etudiant']);

$db = getDB();

// Récupère la dernière soutenance planifiée de l'étudiant
$stmt = $db->prepare("
    SELECT s.id AS soutenance_id, s.date_soutenance, s.heure_debut, s.heure_fin, sa.nom AS salle,
           p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM projets p
    JOIN soutenances s ON p.id = s.projet_id
    JOIN salles sa ON s.salle_id = sa.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE p.etudiant_id = ? AND s.statut IN ('planifiee', 'confirmee')
    ORDER BY s.date_soutenance DESC
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$sout = $stmt->get_result()->fetch_assoc();

if (!$sout) {
    die("<div class='error card'>Aucune soutenance planifiée pour vous actuellement.</div>");
}

// Récupère les membres du jury
$jury = $db->query("
    SELECT CONCAT(u.prenom, ' ', u.nom) AS nom, j.role_jury
    FROM jurys j
    JOIN utilisateurs u ON j.professeur_id = u.id
    WHERE j.soutenance_id = {$sout['soutenance_id']}
    ORDER BY FIELD(j.role_jury, 'president', 'encadrant', 'examinateur')
")->fetch_all(MYSQLI_ASSOC);

// Génération PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 15, 'CONVOCATION A LA SOUTENANCE', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Cher(e) ' . utf8_decode($sout['etudiant']) . ',', 0, 1);
$pdf->Ln(5);

$pdf->MultiCell(0, 8, utf8_decode('Vous êtes convoqué(e) à la soutenance de votre Projet de Fin d’Études intitulé :'));
$pdf->SetFont('Arial', 'B', 13);
$pdf->MultiCell(0, 8, utf8_decode($sout['titre']), 0, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Date : ' . date('d/m/Y', strtotime($sout['date_soutenance'])), 0, 1);
$pdf->Cell(0, 8, 'Heure : ' . substr($sout['heure_debut'], 0, 5) . ' – ' . substr($sout['heure_fin'], 0, 5), 0, 1);
$pdf->Cell(0, 8, 'Salle : ' . utf8_decode($sout['salle']), 0, 1);
$pdf->Ln(10);

$pdf->Cell(0, 10, 'Composition du jury :', 0, 1);
$pdf->SetFont('Arial', '', 11);
foreach ($jury as $m) {
    $pdf->Cell(0, 7, '• ' . utf8_decode($m['nom']) . ' (' . ucfirst($m['role_jury']) . ')', 0, 1);
}

$pdf->Ln(25);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Rabat, le ' . date('d/m/Y'), 0, 1, 'R');
$pdf->Cell(0, 10, 'Le Coordinateur de Filière', 0, 1, 'R');

$pdf->Output('D', 'convocation_soutenance_' . $sout['soutenance_id'] . '.pdf');
?>