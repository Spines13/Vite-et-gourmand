<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';
require_once __DIR__ . '/../../src/Models/PlatModel.php';

AuthService::requireRole('employe', 'administrateur');

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$menu = $id > 0 ? MenuModel::findPourGestion($id) : null;
if ($id > 0 && $menu === null) {
    http_response_code(404);
    exit('Menu introuvable.');
}

$pageTitle = $menu === null ? 'Ajouter un menu' : 'Modifier ' . $menu['titre'];
$erreurs = [];
$themes = MenuModel::listThemes();
$regimes = MenuModel::listRegimes();
$plats = PlatModel::listAll();

$valeurs = $menu ?? [
    'titre' => '', 'description' => '', 'theme_id' => '', 'nombre_personne_minimum' => '',
    'prix_personne_minimum' => '', 'conditions' => '', 'stock_disponible' => '0',
    'regime_ids' => [], 'plat_ids' => [], 'images' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (($_POST['action'] ?? '') === 'supprimer_image' && $menu !== null) {
        MenuModel::supprimerImage((int) ($_POST['image_id'] ?? 0), (int) $menu['menu_id']);
        redirect('/employe/menu-form.php?id=' . $menu['menu_id']);
    }

    $valeurs['titre'] = trim((string) ($_POST['titre'] ?? ''));
    $valeurs['description'] = trim((string) ($_POST['description'] ?? ''));
    $valeurs['theme_id'] = (int) ($_POST['theme_id'] ?? 0);
    $valeurs['nombre_personne_minimum'] = (int) ($_POST['nombre_personne_minimum'] ?? 0);
    $valeurs['prix_personne_minimum'] = (float) ($_POST['prix_personne_minimum'] ?? 0);
    $valeurs['conditions'] = trim((string) ($_POST['conditions'] ?? ''));
    $valeurs['stock_disponible'] = (int) ($_POST['stock_disponible'] ?? 0);
    $valeurs['regime_ids'] = array_map('intval', $_POST['regime_ids'] ?? []);
    $valeurs['plat_ids'] = array_map('intval', $_POST['plat_ids'] ?? []);

    if ($valeurs['titre'] === '' || $valeurs['description'] === '') {
        $erreurs[] = 'Le titre et la description sont obligatoires.';
    }
    if ($valeurs['theme_id'] <= 0) {
        $erreurs[] = 'Merci de choisir un thème.';
    }
    if ($valeurs['nombre_personne_minimum'] < 1) {
        $erreurs[] = 'Le nombre de personnes minimum doit être supérieur à 0.';
    }
    if ($valeurs['prix_personne_minimum'] <= 0) {
        $erreurs[] = 'Le prix doit être supérieur à 0.';
    }

    if (empty($erreurs)) {
        if ($menu === null) {
            $id = MenuModel::creer($valeurs, $valeurs['regime_ids'], $valeurs['plat_ids']);
        } else {
            $id = (int) $menu['menu_id'];
            MenuModel::modifier($id, $valeurs, $valeurs['regime_ids'], $valeurs['plat_ids']);
        }

        if (!empty($_FILES['images']['name'][0])) {
            $extensionsAutorisees = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            $dossierCible = __DIR__ . '/../assets/img/menus/';
            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }
                $extension = strtolower(pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION));
                $typeMime = mime_content_type($tmpName);
                if (!isset($extensionsAutorisees[$extension]) || $extensionsAutorisees[$extension] !== $typeMime) {
                    continue;
                }
                if ($_FILES['images']['size'][$index] > 5 * 1024 * 1024) {
                    continue;
                }
                $nomFichier = 'menu-' . $id . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
                if (move_uploaded_file($tmpName, $dossierCible . $nomFichier)) {
                    MenuModel::ajouterImage($id, 'assets/img/menus/' . $nomFichier);
                }
            }
        }

        flash('success', 'Le menu a été enregistré.');
        redirect('/employe/menu-form.php?id=' . $id);
    }
}

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-espace.php';
?>
    <h1><?= e($pageTitle) ?></h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="formulaire-commande" novalidate>
        <?= csrfField() ?>

        <label for="titre">Titre *</label>
        <input type="text" id="titre" name="titre" value="<?= e($valeurs['titre']) ?>" required>

        <label for="description">Description *</label>
        <textarea id="description" name="description" rows="4" required><?= e($valeurs['description']) ?></textarea>

        <label for="theme_id">Thème *</label>
        <select id="theme_id" name="theme_id" required>
            <option value="">--</option>
            <?php foreach ($themes as $theme): ?>
                <option value="<?= (int) $theme['theme_id'] ?>" <?= (int) $valeurs['theme_id'] === (int) $theme['theme_id'] ? 'selected' : '' ?>><?= e($theme['libelle']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="nombre_personne_minimum">Nombre de personnes minimum *</label>
        <input type="number" id="nombre_personne_minimum" name="nombre_personne_minimum" min="1" value="<?= e((string) $valeurs['nombre_personne_minimum']) ?>" required>

        <label for="prix_personne_minimum">Prix pour ce minimum (€) *</label>
        <input type="number" id="prix_personne_minimum" name="prix_personne_minimum" min="0" step="0.01" value="<?= e((string) $valeurs['prix_personne_minimum']) ?>" required>

        <label for="stock_disponible">Stock disponible (nombre de commandes possibles) *</label>
        <input type="number" id="stock_disponible" name="stock_disponible" min="0" value="<?= e((string) $valeurs['stock_disponible']) ?>" required>

        <label for="conditions">Conditions (délai de commande, précautions de stockage...)</label>
        <textarea id="conditions" name="conditions" rows="3"><?= e($valeurs['conditions'] ?? '') ?></textarea>

        <fieldset>
            <legend>Régime(s)</legend>
            <?php foreach ($regimes as $regime): ?>
                <label>
                    <input type="checkbox" name="regime_ids[]" value="<?= (int) $regime['regime_id'] ?>" <?= in_array((int) $regime['regime_id'], $valeurs['regime_ids'], true) ? 'checked' : '' ?>>
                    <?= e($regime['libelle']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Plats du menu</legend>
            <?php foreach (['entree' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts'] as $categorie => $libelle): ?>
                <p><strong><?= e($libelle) ?></strong></p>
                <?php foreach ($plats as $plat): ?>
                    <?php if ($plat['categorie'] === $categorie): ?>
                        <label>
                            <input type="checkbox" name="plat_ids[]" value="<?= (int) $plat['plat_id'] ?>" <?= in_array((int) $plat['plat_id'], $valeurs['plat_ids'], true) ? 'checked' : '' ?>>
                            <?= e($plat['titre_plat']) ?>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <p class="aide">Pas de plat qui convient ? <a href="/employe/plat-form.php">Créer un plat</a>.</p>
        </fieldset>

        <label for="images">Ajouter des images (JPEG, PNG ou WebP, 5 Mo max)</label>
        <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>

        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>

    <?php if ($menu !== null && !empty($menu['images'])): ?>
        <h2>Images existantes</h2>
        <div class="menu-detail-galerie">
            <?php foreach ($menu['images'] as $image): ?>
                <div>
                    <img src="/<?= e($image['chemin_image']) ?>" alt="">
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="supprimer_image">
                        <input type="hidden" name="image_id" value="<?= (int) $image['image_id'] ?>">
                        <button type="submit" class="btn-secondary">Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
