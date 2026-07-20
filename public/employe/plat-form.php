<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/PlatModel.php';

AuthService::requireRole('employe', 'administrateur');

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$plat = $id > 0 ? PlatModel::find($id) : null;
if ($id > 0 && $plat === null) {
    http_response_code(404);
    exit('Plat introuvable.');
}

$pageTitle = $plat === null ? 'Ajouter un plat' : 'Modifier ' . $plat['titre_plat'];
$erreurs = [];
$allergenes = PlatModel::listAllergenes();
$valeurs = $plat ?? ['titre_plat' => '', 'categorie' => 'entree', 'allergene_ids' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $valeurs['titre_plat'] = trim((string) ($_POST['titre_plat'] ?? ''));
    $valeurs['categorie'] = (string) ($_POST['categorie'] ?? '');
    $valeurs['allergene_ids'] = array_map('intval', $_POST['allergene_ids'] ?? []);

    if ($valeurs['titre_plat'] === '') {
        $erreurs[] = 'Le titre est obligatoire.';
    }
    if (!in_array($valeurs['categorie'], ['entree', 'plat', 'dessert'], true)) {
        $erreurs[] = 'Merci de choisir une catégorie valide.';
    }

    $cheminPhoto = null;
    if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
        $extensionsAutorisees = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $typeMime = mime_content_type($_FILES['photo']['tmp_name']);
        if (isset($extensionsAutorisees[$extension]) && $extensionsAutorisees[$extension] === $typeMime && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            $nomFichier = 'plat-' . bin2hex(random_bytes(6)) . '.' . $extension;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../assets/img/plats/' . $nomFichier)) {
                $cheminPhoto = 'assets/img/plats/' . $nomFichier;
            }
        }
    }

    if (empty($erreurs)) {
        if ($plat === null) {
            $id = PlatModel::creer($valeurs['titre_plat'], $valeurs['categorie'], $cheminPhoto, $valeurs['allergene_ids']);
        } else {
            PlatModel::modifier((int) $plat['plat_id'], $valeurs['titre_plat'], $valeurs['categorie'], $cheminPhoto, $valeurs['allergene_ids']);
            $id = (int) $plat['plat_id'];
        }
        flash('success', 'Le plat a été enregistré.');
        redirect('/employe/plats.php');
    }
}

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1><?= e($pageTitle) ?></h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="formulaire-commande" novalidate>
        <?= csrfField() ?>

        <label for="titre_plat">Titre *</label>
        <input type="text" id="titre_plat" name="titre_plat" value="<?= e($valeurs['titre_plat']) ?>" required>

        <label for="categorie">Catégorie *</label>
        <select id="categorie" name="categorie" required>
            <option value="entree" <?= $valeurs['categorie'] === 'entree' ? 'selected' : '' ?>>Entrée</option>
            <option value="plat" <?= $valeurs['categorie'] === 'plat' ? 'selected' : '' ?>>Plat</option>
            <option value="dessert" <?= $valeurs['categorie'] === 'dessert' ? 'selected' : '' ?>>Dessert</option>
        </select>

        <fieldset>
            <legend>Allergènes</legend>
            <?php foreach ($allergenes as $allergene): ?>
                <label>
                    <input type="checkbox" name="allergene_ids[]" value="<?= (int) $allergene['allergene_id'] ?>" <?= in_array((int) $allergene['allergene_id'], $valeurs['allergene_ids'], true) ? 'checked' : '' ?>>
                    <?= e($allergene['libelle']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <label for="photo">Photo (JPEG, PNG ou WebP, 5 Mo max)</label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">

        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
