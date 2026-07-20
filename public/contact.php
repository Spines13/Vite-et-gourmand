<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../src/Models/ContactModel.php';

$pageTitle = 'Contact';
$erreurs = [];
$envoye = false;
$valeurs = ['titre' => '', 'description' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $valeurs['titre'] = trim((string) ($_POST['titre'] ?? ''));
    $valeurs['description'] = trim((string) ($_POST['description'] ?? ''));
    $valeurs['email'] = trim((string) ($_POST['email'] ?? ''));

    if ($valeurs['titre'] === '' || $valeurs['description'] === '') {
        $erreurs[] = 'Merci de renseigner le titre et votre message.';
    }
    if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email n\'est pas valide.';
    }

    if (empty($erreurs)) {
        ContactModel::create($valeurs['titre'], $valeurs['description'], $valeurs['email']);
        MailService::envoyerContact(env('CONTACT_EMAIL', 'contact@vite-et-gourmand.fr'), '', $valeurs['email'], $valeurs['titre'], $valeurs['description']);
        $envoye = true;
    }
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <h1>Contactez-nous</h1>

    <?php if ($envoye): ?>
        <p role="status">Merci, votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.</p>
    <?php else: ?>
        <?php if (!empty($erreurs)): ?>
            <ul class="erreurs" role="alert"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>

        <form method="post" class="formulaire-page" novalidate>
            <?= csrfField() ?>

            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" value="<?= e($valeurs['titre']) ?>" required>

            <label for="description">Votre message *</label>
            <textarea id="description" name="description" rows="6" required><?= e($valeurs['description']) ?></textarea>

            <label for="email">Votre email *</label>
            <input type="email" id="email" name="email" value="<?= e($valeurs['email']) ?>" required>

            <button type="submit" class="btn-primary">Envoyer</button>
        </form>
    <?php endif; ?>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
