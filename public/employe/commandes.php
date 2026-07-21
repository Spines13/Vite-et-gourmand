<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Gestion des commandes';

$statuts = getPDO()->query('SELECT code, libelle FROM statut_commande ORDER BY ordre')->fetchAll();

$filtres = [];
if (!empty($_GET['statut_code'])) {
    $filtres['statut_code'] = (string) $_GET['statut_code'];
}
if (!empty($_GET['recherche'])) {
    $filtres['recherche'] = (string) $_GET['recherche'];
}

$commandes = CommandeModel::listAll($filtres);

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-espace.php';
?>
    <h1>Gestion des commandes</h1>

    <form method="get" class="filtres-menus" aria-label="Filtrer les commandes">
        <div>
            <label for="statut_code">Statut</label>
            <select id="statut_code" name="statut_code">
                <option value="">Tous</option>
                <?php foreach ($statuts as $statut): ?>
                    <option value="<?= e($statut['code']) ?>" <?= ($filtres['statut_code'] ?? '') === $statut['code'] ? 'selected' : '' ?>><?= e($statut['libelle']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="recherche">Client ou numéro</label>
            <input type="text" id="recherche" name="recherche" value="<?= e($filtres['recherche'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-secondary">Filtrer</button>
    </form>

    <table class="table-commandes">
        <thead>
            <tr>
                <th scope="col">Numéro</th>
                <th scope="col">Client</th>
                <th scope="col">Menu</th>
                <th scope="col">Date prestation</th>
                <th scope="col">Statut</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($commandes as $commande): ?>
                <tr>
                    <td><?= e($commande['numero_commande']) ?></td>
                    <td><?= e($commande['utilisateur_prenom'] . ' ' . $commande['utilisateur_nom']) ?></td>
                    <td><?= e($commande['menu_titre']) ?></td>
                    <td><?= e($commande['date_prestation']) ?></td>
                    <td><?= e($commande['statut_libelle']) ?></td>
                    <td><a href="/employe/commande.php?id=<?= (int) $commande['commande_id'] ?>">Détail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($commandes)): ?>
                <tr><td colspan="6">Aucune commande ne correspond à ces critères.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
