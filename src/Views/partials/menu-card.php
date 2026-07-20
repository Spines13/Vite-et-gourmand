<?php
/** @var array $menu */
?>
<article class="menu-card">
    <img src="/<?= e($menu['image'] ?? 'assets/img/menus/placeholder.jpg') ?>" alt="" loading="lazy">
    <div class="menu-card-body">
        <p class="menu-card-theme"><?= e($menu['theme']) ?></p>
        <h3><?= e($menu['titre']) ?></h3>
        <p class="menu-card-description"><?= e(mb_strimwidth($menu['description'], 0, 140, '…')) ?></p>
        <p class="menu-card-infos">
            À partir de <?= (int) $menu['nombre_personne_minimum'] ?> personnes —
            <strong><?= number_format((float) $menu['prix_personne_minimum'], 2, ',', ' ') ?> €</strong>
        </p>
        <?php if ((int) $menu['stock_disponible'] <= 0): ?>
            <p class="menu-card-rupture">Rupture de stock</p>
        <?php endif; ?>
        <a class="btn-secondary" href="/menu-detail.php?id=<?= (int) $menu['menu_id'] ?>">Voir le détail</a>
    </div>
</article>
