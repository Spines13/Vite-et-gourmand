<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

AuthService::requireGuest();

$pageTitle = 'Réinitialiser le mot de passe';
$erreurs = [];
$tokenClair = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$tokenHash = $tokenClair !== '' ? hash('sha256', $tokenClair) : '';
$utilisateur = $tokenHash !== '' ? UtilisateurModel::findByValidResetTokenHash($tokenHash) : null;

if ($utilisateur === null) {
    $erreurs[] = 'Ce lien de réinitialisation est invalide ou a expiré. Merci de refaire une demande.';
}

if ($utilisateur !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
    $motDePasseConfirmation = (string) ($_POST['mot_de_passe_confirmation'] ?? '');

    if (!isPasswordStrongEnough($motDePasse)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
    } elseif ($motDePasse !== $motDePasseConfirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (empty($erreurs)) {
        UtilisateurModel::updatePassword((int) $utilisateur['utilisateur_id'], $motDePasse);
        UtilisateurModel::clearResetToken((int) $utilisateur['utilisateur_id']);
        flash('success', 'Votre mot de passe a été réinitialisé, vous pouvez vous connecter.');
        redirect('/connexion.php');
    }
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <section class="formulaire-page">
        <h1>Réinitialiser le mot de passe</h1>

        <?php if (!empty($erreurs)): ?>
            <ul class="erreurs" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($utilisateur !== null): ?>
            <form method="post" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($tokenClair) ?>">

                <label for="mot_de_passe">Nouveau mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="new-password">

                <label for="mot_de_passe_confirmation">Confirmer le nouveau mot de passe</label>
                <input type="password" id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" required autocomplete="new-password">

                <button type="submit" class="btn-primary">Réinitialiser</button>
            </form>
        <?php else: ?>
            <p><a href="/mot-de-passe-oublie.php">Faire une nouvelle demande</a></p>
        <?php endif; ?>
    </section>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
