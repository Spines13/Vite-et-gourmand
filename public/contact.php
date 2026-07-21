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
    <div class="contact-entete">
        <h1>Contactez-nous</h1>
        <p>Une question sur un menu, une demande particulière pour votre événement ? Écrivez-nous,
        nous vous répondons rapidement.</p>
    </div>

    <div class="contact-layout">
        <div class="contact-infos">
            <div class="contact-info-item">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                </span>
                <div>
                    <p class="contact-info-titre">Réponse rapide</p>
                    <p>Nous répondons à toutes les demandes sous 48h ouvrées.</p>
                </div>
            </div>
            <div class="contact-info-item">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                </span>
                <div>
                    <p class="contact-info-titre">Échange direct</p>
                    <p>Votre message est lu directement par l'équipe Vite &amp; Gourmand.</p>
                </div>
            </div>
            <div class="contact-info-item">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
                </span>
                <div>
                    <p class="contact-info-titre">Données confidentielles</p>
                    <p>Vos informations ne sont utilisées que pour vous répondre (voir nos <a href="/mentions-legales.php">mentions légales</a>).</p>
                </div>
            </div>
        </div>

        <div class="contact-form-carte">
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
        </div>
    </div>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
