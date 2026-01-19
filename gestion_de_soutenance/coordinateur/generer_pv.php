<?php
ob_start(); // Start output buffering to catch any accidental output

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../fpdf/fpdf.php';

requireLogin();
checkRole(['coordinateur', 'directeur']);

$soutenance_id = (int)($_GET['soutenance_id'] ?? 0);
if ($soutenance_id <= 0) {
    die("Soutenance non spécifiée. Utilisez ?soutenance_id=XX");
}

$db = getDB();

// Fetch soutenance details (with all needed fields)
$sout = $db->query("
    SELECT s.id, s.date_soutenance, s.heure_debut, s.heure_fin, s.note_finale, s.mention,
           p.titre, 
           CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE s.id = $soutenance_id
    LIMIT 1
")->fetch_assoc();

if (!$sout) {
    ob_end_clean();
    die("Soutenance non trouvée dans la base de données.");
}

// Fetch jury
$jury = $db->query("
    SELECT CONCAT(u.prenom, ' ', u.nom) AS nom, j.role_jury, j.note_attribuee
    FROM jurys j
    JOIN utilisateurs u ON j.professeur_id = u.id
    WHERE j.soutenance_id = $soutenance_id
")->fetch_all(MYSQLI_ASSOC);

// Calculate final grade if missing
if (empty($sout['note_finale'])) {
    calculerNoteFinaleEtMention($soutenance_id, $db);
    $sout = $db->query("SELECT * FROM soutenances WHERE id = $soutenance_id")->fetch_assoc();
}

// Clean any previous output
ob_end_clean();

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 15, utf8_decode('PROCÈS-VERBAL DE SOUTENANCE'), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Helvetica', '', 12);
$pdf->Cell(0, 10, 'Etudiant : ' . utf8_decode($sout['etudiant'] ?? '—'), 0, 1);
$pdf->Cell(0, 10, 'Projet : ' . utf8_decode($sout['titre'] ?? '—'), 0, 1);
$pdf->Cell(0, 10, 'Date : ' . ($sout['date_soutenance'] ? date('d/m/Y', strtotime($sout['date_soutenance'])) : '—'), 0, 1);
$pdf->Ln(8);

$pdf->Cell(0, 10, 'Note finale : ' . ($sout['note_finale'] ?? 'Non calculee') . '/20', 0, 1);
$pdf->Cell(0, 10, 'Mention : ' . ($sout['mention'] ?? 'Non attribuee'), 0, 1);
$pdf->Ln(10);

$pdf->Cell(0, 10, 'Jury :', 0, 1);
foreach ($jury as $m) {
    $note = $m['note_attribuee'] ? $m['note_attribuee'] . '/20' : 'Non saisi';
    $pdf->Cell(0, 8, '• ' . utf8_decode($m['nom']) . ' (' . ucfirst($m['role_jury']) . ') : ' . $note, 0, 1);
}

$pdf->Ln(20);
$pdf->SetFont('Helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Signe le ' . date('d/m/Y'), 0, 1, 'R');

$pdf->Output('D', 'pv_soutenance_' . $soutenance_id . '.pdf');
?>