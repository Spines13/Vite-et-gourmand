<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/UtilisateurModel.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';
require_once __DIR__ . '/../../src/Models/AvisModel.php';

AuthService::requireRole('administrateur');
$pageTitle = 'Espace administrateur';

$employes = UtilisateurModel::listEmployes();
$commandesEnAttente = CommandeModel::listAll(['statut_code' => 'en_attente']);
$avisEnAttente = AvisModel::listEnAttente();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-admin.php';
?>
    <h1>Espace administrateur</h1>
    <ul>
        <li><?= count($employes) ?> compte(s) employé</li>
        <li><?= count($commandesEnAttente) ?> commande(s) en attente de validation</li>
        <li><?= count($avisEnAttente) ?> avis en attente de modération</li>
    </ul>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
