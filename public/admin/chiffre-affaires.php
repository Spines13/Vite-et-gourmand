<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Services/AnalyticsService.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';

AuthService::requireRole('administrateur');
$pageTitle = "Chiffre d'affaires";

$disponible = AnalyticsService::estDisponible();
$menus = MenuModel::listPourGestion();

$menuId = !empty($_GET['menu_id']) && ctype_digit((string) $_GET['menu_id']) ? (int) $_GET['menu_id'] : null;
$dateDebut = !empty($_GET['date_debut']) ? (string) $_GET['date_debut'] : null;
$dateFin = !empty($_GET['date_fin']) ? (string) $_GET['date_fin'] : null;

$resultats = $disponible ? AnalyticsService::chiffreAffairesParMenu($menuId, $dateDebut, $dateFin) : [];
$total = array_sum(array_column($resultats, 'chiffre_affaires'));

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-admin.php';
?>
    <h1>Chiffre d'affaires par menu</h1>

    <?php if (!$disponible): ?>
        <p class="erreurs" role="alert">MongoDB n'est pas configuré sur cet environnement. Voir <code>database/mongodb/README.md</code> pour l'installation.</p>
    <?php else: ?>
        <form method="get" class="filtres-menus" aria-label="Filtrer le chiffre d'affaires">
            <div>
                <label for="menu_id">Menu</label>
                <select id="menu_id" name="menu_id">
                    <option value="">Tous les menus</option>
                    <?php foreach ($menus as $menu): ?>
                        <option value="<?= (int) $menu['menu_id'] ?>" <?= $menuId === (int) $menu['menu_id'] ? 'selected' : '' ?>><?= e($menu['titre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="date_debut">Du</label>
                <input type="date" id="date_debut" name="date_debut" value="<?= e($dateDebut ?? '') ?>">
            </div>
            <div>
                <label for="date_fin">Au</label>
                <input type="date" id="date_fin" name="date_fin" value="<?= e($dateFin ?? '') ?>">
            </div>
            <button type="submit" class="btn-secondary">Filtrer</button>
        </form>

        <table class="table-commandes">
            <thead><tr><th scope="col">Menu</th><th scope="col">Nombre de commandes</th><th scope="col">Chiffre d'affaires</th></tr></thead>
            <tbody>
                <?php foreach ($resultats as $ligne): ?>
                    <tr>
                        <td><?= e($ligne['menu_titre']) ?></td>
                        <td><?= (int) $ligne['nombre_commandes'] ?></td>
                        <td><?= number_format($ligne['chiffre_affaires'], 2, ',', ' ') ?> €</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($resultats)): ?>
                    <tr><td colspan="3">Aucune donnée pour ces critères.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><td><strong>Total</strong></td><td></td><td><strong><?= number_format($total, 2, ',', ' ') ?> €</strong></td></tr>
            </tfoot>
        </table>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
