<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
checkRole(['directeur']);

$db = getDB();

// Stats simples
$total_soutenances = $db->query("SELECT COUNT(*) FROM soutenances")->fetch_row()[0];
$soutenances_terminees = $db->query("SELECT COUNT(*) FROM soutenances WHERE statut IN ('soutenu', 'terminee')")->fetch_row()[0];
$moyenne_notes = $db->query("SELECT AVG(note_finale) FROM soutenances WHERE note_finale IS NOT NULL")->fetch_row()[0] ?? 0;
$projet_sans_encadrant = $db->query("SELECT COUNT(*) FROM projets WHERE encadrant_id IS NULL")->fetch_row()[0];
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Statistiques globales</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Total soutenances</h5>
                            <h2 class="text-primary"><?= $total_soutenances ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Soutenances terminées</h5>
                            <h2 class="text-success"><?= $soutenances_terminees ?></h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Moyenne des notes</h5>
                            <h2 class="text-info"><?= round($moyenne_notes, 2) ?>/20</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card text-center bg-light">
                        <div class="card-body">
                            <h5>Projets sans encadrant</h5>
                            <h2 class="text-danger"><?= $projet_sans_encadrant ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="../dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
            </div>
        </div>
    </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>