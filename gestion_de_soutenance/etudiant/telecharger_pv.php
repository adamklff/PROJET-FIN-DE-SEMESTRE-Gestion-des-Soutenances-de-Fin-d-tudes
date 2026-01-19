<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../fpdf/fpdf.php';

requireLogin();
checkRole(['etudiant']);

$db = getDB();

$stmt = $db->prepare("
    SELECT s.id, s.note_finale, s.mention, s.date_soutenance,
           p.titre, CONCAT(e.prenom, ' ', e.nom) AS etudiant
    FROM soutenances s
    JOIN projets p ON s.projet_id = p.id
    JOIN utilisateurs e ON p.etudiant_id = e.id
    WHERE p.etudiant_id = ? AND s.statut = 'terminee'
    ORDER BY s.date_soutenance DESC
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$sout = $stmt->get_result()->fetch_assoc();

if (!$sout) {
    die("<div class='error card'>Aucun PV disponible (soutenance non terminée).</div>");
}

// Jury pour le PV
$jury = $db->query("
    SELECT CONCAT(u.prenom, ' ', u.nom) AS nom, j.role_jury, j.note_attribuee
    FROM jurys j
    JOIN utilisateurs u ON j.professeur_id = u.id
    WHERE j.soutenance_id = {$sout['id']}
")->fetch_all(MYSQLI_ASSOC);

// PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 15, utf8_decode('PROCÈS-VERBAL DE SOUTENANCE'), 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Helvetica', '', 12);
$pdf->Cell(0, 10, 'Étudiant : ' . utf8_decode($sout['etudiant']), 0, 1);
$pdf->Cell(0, 10, 'Projet : ' . utf8_decode($sout['titre']), 0, 1);
$pdf->Cell(0, 10, 'Date : ' . date('d/m/Y', strtotime($sout['date_soutenance'])), 0, 1);
$pdf->Ln(8);

$pdf->Cell(0, 10, 'Note finale : ' . $sout['note_finale'] . '/20', 0, 1);
$pdf->Cell(0, 10, 'Mention : ' . $sout['mention'], 0, 1);
$pdf->Ln(10);

$pdf->Cell(0, 10, 'Jury :', 0, 1);
foreach ($jury as $m) {
    $note = $m['note_attribuee'] ? $m['note_attribuee'] . '/20' : '—';
    $pdf->Cell(0, 8, '• ' . utf8_decode($m['nom']) . ' (' . ucfirst($m['role_jury']) . ') : ' . $note, 0, 1);
}

$pdf->Ln(20);
$pdf->SetFont('Helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Signé le ' . date('d/m/Y'), 0, 1, 'R');

$pdf->Output('D', 'pv_soutenance_' . $sout['id'] . '.pdf');
?>