<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';
require_once __DIR__ . '/../../src/Models/AvisModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Espace employé';

$commandesEnAttente = CommandeModel::listAll(['statut_code' => 'en_attente']);
$avisEnAttente = AvisModel::listEnAttente();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1>Espace employé</h1>
    <ul>
        <li><?= count($commandesEnAttente) ?> commande(s) en attente de validation</li>
        <li><?= count($avisEnAttente) ?> avis en attente de modération</li>
    </ul>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
