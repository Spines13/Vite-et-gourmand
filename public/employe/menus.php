<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Gestion des menus';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['menu_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'basculer_actif' && $id > 0) {
        $menu = MenuModel::findPourGestion($id);
        if ($menu !== null) {
            MenuModel::setActif($id, !((bool) $menu['actif']));
            flash('success', 'Le menu a été mis à jour.');
        }
    }
    redirect('/employe/menus.php');
}

$menus = MenuModel::listPourGestion();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1>Gestion des menus</h1>
    <p><a class="btn-primary" href="/employe/menu-form.php">Ajouter un menu</a></p>

    <table class="table-commandes">
        <thead>
            <tr>
                <th scope="col">Titre</th>
                <th scope="col">Thème</th>
                <th scope="col">Stock</th>
                <th scope="col">Statut</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menus as $menu): ?>
                <tr>
                    <td><?= e($menu['titre']) ?></td>
                    <td><?= e($menu['theme']) ?></td>
                    <td><?= (int) $menu['stock_disponible'] ?></td>
                    <td><?= $menu['actif'] ? 'Actif' : 'Inactif' ?></td>
                    <td>
                        <a href="/employe/menu-form.php?id=<?= (int) $menu['menu_id'] ?>">Modifier</a>
                        <form method="post" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="basculer_actif">
                            <input type="hidden" name="menu_id" value="<?= (int) $menu['menu_id'] ?>">
                            <button type="submit" class="btn-secondary"><?= $menu['actif'] ? 'Supprimer' : 'Réactiver' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
