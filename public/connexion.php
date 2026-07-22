<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

AuthService::requireGuest();

$pageTitle = 'Connexion';
$erreur = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if (AuthService::attemptLogin($email, $motDePasse)) {
        $destination = $_SESSION['redirect_after_login'] ?? match ($_SESSION['role']) {
            'administrateur' => '/admin/index.php',
            'employe'        => '/employe/index.php',
            default          => '/utilisateur/index.php',
        };
        unset($_SESSION['redirect_after_login']);
        flash('success', 'Vous êtes connecté(e).');
        redirect($destination);
    }

    $erreur = 'Email ou mot de passe incorrect, ou compte désactivé.';
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <section class="formulaire-page">
        <h1>Connexion</h1>

        <?php if ($erreur !== null): ?>
            <p class="erreurs" role="alert"><?= e($erreur) ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">

            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">

            <button type="submit" class="btn-primary">Se connecter</button>
        </form>

        <p><a href="/mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
        <p><a href="/inscription.php">Créer un compte</a></p>
    </section>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
