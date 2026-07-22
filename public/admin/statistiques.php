<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Services/AnalyticsService.php';

AuthService::requireRole('administrateur');
$pageTitle = 'Statistiques des commandes';

$disponible = AnalyticsService::estDisponible();
$donnees = $disponible ? AnalyticsService::commandesParMenu() : [];
$maximum = $donnees === [] ? 1 : max(array_column($donnees, 'total_commandes'));

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-admin.php';
?>
    <h1>Nombre de commandes par menu</h1>

    <?php if (!$disponible): ?>
        <p class="erreurs" role="alert">MongoDB n'est pas configuré sur cet environnement. Voir <code>database/mongodb/README.md</code> pour l'installation.</p>
    <?php elseif (empty($donnees)): ?>
        <p>Aucune commande enregistrée pour le moment.</p>
    <?php else: ?>
        <div class="graphique-barres" role="img" aria-label="Diagramme du nombre de commandes par menu">
            <?php foreach ($donnees as $ligne): ?>
                <div class="graphique-barre-ligne">
                    <span class="graphique-barre-label"><?= e($ligne['menu_titre']) ?></span>
                    <span class="graphique-barre-piste">
                        <span class="graphique-barre-remplissage" style="width: <?= (int) round($ligne['total_commandes'] / $maximum * 100) ?>%"></span>
                    </span>
                    <span class="graphique-barre-valeur"><?= (int) $ligne['total_commandes'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <table class="table-commandes">
            <caption class="visually-hidden">Nombre de commandes par menu</caption>
            <thead><tr><th scope="col">Menu</th><th scope="col">Nombre de commandes</th></tr></thead>
            <tbody>
                <?php foreach ($donnees as $ligne): ?>
                    <tr><td><?= e($ligne['menu_titre']) ?></td><td><?= (int) $ligne['total_commandes'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
