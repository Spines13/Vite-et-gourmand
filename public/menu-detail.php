<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Models/MenuModel.php';

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$menu = $id > 0 ? MenuModel::find($id) : null;

if ($menu === null) {
    http_response_code(404);
    $pageTitle = 'Menu introuvable';
    require __DIR__ . '/../src/Views/partials/header.php';
    echo '<p>Ce menu n\'existe pas ou n\'est plus disponible.</p><p><a href="/menus.php">Retour aux menus</a></p>';
    require __DIR__ . '/../src/Views/partials/footer.php';
    exit;
}

$pageTitle = $menu['titre'];
$libellesCategorie = ['entree' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts'];

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <article class="menu-detail">
        <p class="menu-card-theme"><?= e($menu['theme']) ?></p>
        <h1><?= e($menu['titre']) ?></h1>

        <?php if (!empty($menu['images'])): ?>
            <div class="menu-detail-galerie">
                <?php foreach ($menu['images'] as $image): ?>
                    <img src="/<?= e($image) ?>" alt="">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="menu-detail-description"><?= nl2br(e($menu['description'])) ?></p>

        <?php if (!empty($menu['regimes'])): ?>
            <p><strong>Régime(s) :</strong> <?= e(implode(', ', $menu['regimes'])) ?></p>
        <?php endif; ?>

        <p>
            <strong>À partir de <?= (int) $menu['nombre_personne_minimum'] ?> personnes</strong> —
            <?= number_format((float) $menu['prix_personne_minimum'], 2, ',', ' ') ?> €
        </p>

        <?php foreach ($libellesCategorie as $categorie => $libelle): ?>
            <?php if (!empty($menu['plats'][$categorie])): ?>
                <h2><?= e($libelle) ?></h2>
                <ul class="menu-detail-plats">
                    <?php foreach ($menu['plats'][$categorie] as $plat): ?>
                        <li>
                            <?= e($plat['titre_plat']) ?>
                            <?php if (!empty($plat['allergenes'])): ?>
                                <span class="allergenes">— Allergènes : <?= e($plat['allergenes']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($menu['conditions'])): ?>
            <div class="menu-detail-conditions" role="note">
                <h2>Conditions de commande</h2>
                <p><?= nl2br(e($menu['conditions'])) ?></p>
            </div>
        <?php endif; ?>

        <?php if ((int) $menu['stock_disponible'] <= 0): ?>
            <p class="menu-card-rupture">Ce menu est actuellement en rupture de stock.</p>
        <?php else: ?>
            <a class="btn-primary" href="/commande.php?menu_id=<?= (int) $menu['menu_id'] ?>">Commander ce menu</a>
        <?php endif; ?>
    </article>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
