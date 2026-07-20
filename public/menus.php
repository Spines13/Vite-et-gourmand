<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Models/MenuModel.php';

$pageTitle = 'Nos menus';

$filtres = [];
foreach (['prix_min', 'prix_max'] as $cle) {
    if (isset($_GET[$cle]) && is_numeric($_GET[$cle])) {
        $filtres[$cle] = (float) $_GET[$cle];
    }
}
foreach (['theme_id', 'regime_id', 'personnes_min'] as $cle) {
    if (isset($_GET[$cle]) && ctype_digit((string) $_GET[$cle])) {
        $filtres[$cle] = (int) $_GET[$cle];
    }
}

$menus = MenuModel::listActive($filtres);
$themes = MenuModel::listThemes();
$regimes = MenuModel::listRegimes();

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <h1>Nos menus</h1>

    <form id="filtres-menus" class="filtres-menus" method="get" aria-label="Filtrer les menus">
        <div>
            <label for="prix_min">Prix minimum (€)</label>
            <input type="number" id="prix_min" name="prix_min" min="0" step="1" value="<?= e((string) ($filtres['prix_min'] ?? '')) ?>">
        </div>
        <div>
            <label for="prix_max">Prix maximum (€)</label>
            <input type="number" id="prix_max" name="prix_max" min="0" step="1" value="<?= e((string) ($filtres['prix_max'] ?? '')) ?>">
        </div>
        <div>
            <label for="theme_id">Thème</label>
            <select id="theme_id" name="theme_id">
                <option value="">Tous</option>
                <?php foreach ($themes as $theme): ?>
                    <option value="<?= (int) $theme['theme_id'] ?>" <?= (int) ($filtres['theme_id'] ?? 0) === (int) $theme['theme_id'] ? 'selected' : '' ?>>
                        <?= e($theme['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="regime_id">Régime</label>
            <select id="regime_id" name="regime_id">
                <option value="">Tous</option>
                <?php foreach ($regimes as $regime): ?>
                    <option value="<?= (int) $regime['regime_id'] ?>" <?= (int) ($filtres['regime_id'] ?? 0) === (int) $regime['regime_id'] ? 'selected' : '' ?>>
                        <?= e($regime['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="personnes_min">Nombre de personnes</label>
            <input type="number" id="personnes_min" name="personnes_min" min="1" step="1" value="<?= e((string) ($filtres['personnes_min'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn-secondary">Filtrer</button>
    </form>

    <p id="menus-statut" role="status" class="visually-hidden"></p>

    <div id="menus-resultats" class="menus-grille">
        <?php foreach ($menus as $menu): ?>
            <?php require __DIR__ . '/../src/Views/partials/menu-card.php'; ?>
        <?php endforeach; ?>
        <?php if (empty($menus)): ?>
            <p>Aucun menu ne correspond à ces critères pour le moment.</p>
        <?php endif; ?>
    </div>

    <script src="/assets/js/menus.js" defer></script>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
