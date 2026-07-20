<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';

$utilisateur = AuthService::requireLogin();
$pageTitle = 'Mes commandes';

$commandes = CommandeModel::listByUtilisateur((int) $utilisateur['utilisateur_id']);

require __DIR__ . '/../../src/Views/partials/header.php';
?>
    <h1>Mes commandes</h1>
    <p><a href="/utilisateur/profil.php">Modifier mes informations personnelles</a></p>

    <?php if (empty($commandes)): ?>
        <p>Vous n'avez pas encore passé de commande. <a href="/menus.php">Découvrir nos menus</a></p>
    <?php else: ?>
        <table class="table-commandes">
            <thead>
                <tr>
                    <th scope="col">Numéro</th>
                    <th scope="col">Menu</th>
                    <th scope="col">Date de prestation</th>
                    <th scope="col">Total</th>
                    <th scope="col">Statut</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?= e($commande['numero_commande']) ?></td>
                        <td><?= e($commande['menu_titre']) ?></td>
                        <td><?= e($commande['date_prestation']) ?></td>
                        <td><?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €</td>
                        <td><?= e($commande['statut_libelle']) ?></td>
                        <td><a href="/utilisateur/commande.php?id=<?= (int) $commande['commande_id'] ?>">Détail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
