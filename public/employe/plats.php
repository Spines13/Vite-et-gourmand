<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/PlatModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Gestion des plats';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    verifyCsrf();
    PlatModel::supprimer((int) ($_POST['plat_id'] ?? 0));
    flash('success', 'Le plat a été supprimé.');
    redirect('/employe/plats.php');
}

$plats = PlatModel::listAll();
$libellesCategorie = ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'];

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1>Gestion des plats</h1>
    <p><a class="btn-primary" href="/employe/plat-form.php">Ajouter un plat</a></p>

    <table class="table-commandes">
        <thead>
            <tr><th scope="col">Titre</th><th scope="col">Catégorie</th><th scope="col"></th></tr>
        </thead>
        <tbody>
            <?php foreach ($plats as $plat): ?>
                <tr>
                    <td><?= e($plat['titre_plat']) ?></td>
                    <td><?= e($libellesCategorie[$plat['categorie']]) ?></td>
                    <td>
                        <a href="/employe/plat-form.php?id=<?= (int) $plat['plat_id'] ?>">Modifier</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce plat ?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                            <button type="submit" class="btn-secondary">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
