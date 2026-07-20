<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Models/CommandeModel.php';

$utilisateur = AuthService::requireLogin();
$pageTitle = 'Commande confirmée';

$numero = trim((string) ($_GET['numero'] ?? ''));
$commande = $numero !== '' ? CommandeModel::findByNumeroPourUtilisateur($numero, (int) $utilisateur['utilisateur_id']) : null;

if ($commande === null) {
    http_response_code(404);
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <?php if ($commande === null): ?>
        <h1>Commande introuvable</h1>
        <p><a href="/menus.php">Retour aux menus</a></p>
    <?php else: ?>
        <h1>Merci, votre commande est confirmée !</h1>
        <p>Numéro de commande : <strong><?= e($commande['numero_commande']) ?></strong></p>
        <p>Menu : <?= e($commande['menu_titre']) ?></p>
        <p>Nombre de personnes : <?= (int) $commande['nombre_personnes'] ?></p>
        <p>Date de prestation : <?= e($commande['date_prestation']) ?> à <?= e(substr($commande['heure_livraison'], 0, 5)) ?></p>
        <p>Livraison : <?= e($commande['adresse_livraison']) ?>, <?= e($commande['code_postal_livraison']) ?> <?= e($commande['ville_livraison']) ?></p>
        <p>Frais de livraison : <?= number_format((float) $commande['frais_livraison'], 2, ',', ' ') ?> €</p>
        <p><strong>Total : <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €</strong></p>
        <p>Statut : <?= e($commande['statut_libelle']) ?></p>
        <p>Un email de confirmation vous a été envoyé à <?= e($utilisateur['email']) ?>.</p>
    <?php endif; ?>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
