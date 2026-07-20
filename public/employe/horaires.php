<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/HoraireModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Gestion des horaires';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $horaires = [];
    foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $jour) {
        $horaires[$jour] = [
            'ferme'           => isset($_POST['ferme'][$jour]),
            'heure_ouverture' => trim((string) ($_POST['heure_ouverture'][$jour] ?? '')) ?: null,
            'heure_fermeture' => trim((string) ($_POST['heure_fermeture'][$jour] ?? '')) ?: null,
        ];
    }
    HoraireModel::mettreAJour($horaires);
    flash('success', 'Les horaires ont été mis à jour.');
    redirect('/employe/horaires.php');
}

$horaires = HoraireModel::listAll();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1>Gestion des horaires</h1>

    <form method="post" class="formulaire-commande" novalidate>
        <?= csrfField() ?>
        <?php foreach ($horaires as $horaire): ?>
            <fieldset>
                <legend><?= e(ucfirst($horaire['jour'])) ?></legend>
                <label>
                    <input type="checkbox" name="ferme[<?= e($horaire['jour']) ?>]" <?= $horaire['ferme'] ? 'checked' : '' ?>>
                    Fermé ce jour-là
                </label>
                <label for="ouverture-<?= e($horaire['jour']) ?>">Heure d'ouverture</label>
                <input type="time" id="ouverture-<?= e($horaire['jour']) ?>" name="heure_ouverture[<?= e($horaire['jour']) ?>]" value="<?= e(substr((string) $horaire['heure_ouverture'], 0, 5)) ?>">

                <label for="fermeture-<?= e($horaire['jour']) ?>">Heure de fermeture</label>
                <input type="time" id="fermeture-<?= e($horaire['jour']) ?>" name="heure_fermeture[<?= e($horaire['jour']) ?>]" value="<?= e(substr((string) $horaire['heure_fermeture'], 0, 5)) ?>">
            </fieldset>
        <?php endforeach; ?>

        <button type="submit" class="btn-primary">Enregistrer les horaires</button>
    </form>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
