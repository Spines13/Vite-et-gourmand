<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/MailService.php';

AuthService::requireGuest();

$pageTitle = 'Mot de passe oublié';
$messageEnvoye = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $utilisateur = filter_var($email, FILTER_VALIDATE_EMAIL) ? UtilisateurModel::findByEmail($email) : null;

    // Réponse identique que le compte existe ou non, pour ne pas révéler les adresses inscrites.
    if ($utilisateur !== null) {
        $tokenClair = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenClair);
        $expiration = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        UtilisateurModel::setResetToken((int) $utilisateur['utilisateur_id'], $tokenHash, $expiration);

        $lien = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
            . '/reinitialiser-mot-de-passe.php?token=' . $tokenClair;

        MailService::envoyerReinitialisationMotDePasse($utilisateur['email'], $utilisateur['prenom'], $lien);
    }

    $messageEnvoye = true;
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <section class="formulaire-page">
        <h1>Mot de passe oublié</h1>

        <?php if ($messageEnvoye): ?>
            <p role="status">Si un compte existe avec cette adresse, un email de réinitialisation vient de vous être envoyé.</p>
        <?php else: ?>
            <p>Indiquez votre adresse email, nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
            <form method="post" novalidate>
                <?= csrfField() ?>
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
                <button type="submit" class="btn-primary">Envoyer le lien</button>
            </form>
        <?php endif; ?>
    </section>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
