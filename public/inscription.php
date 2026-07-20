<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/MailService.php';

AuthService::requireGuest();

$pageTitle = 'Créer un compte';
$erreurs = [];
$valeurs = ['nom' => '', 'prenom' => '', 'telephone' => '', 'email' => '', 'adresse_postale' => '', 'ville' => '', 'code_postal' => '', 'pays' => 'France'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach (array_keys($valeurs) as $champ) {
        $valeurs[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
    $motDePasseConfirmation = (string) ($_POST['mot_de_passe_confirmation'] ?? '');

    foreach (['nom', 'prenom', 'telephone', 'email', 'adresse_postale', 'ville', 'code_postal'] as $champObligatoire) {
        if ($valeurs[$champObligatoire] === '') {
            $erreurs[] = 'Merci de renseigner tous les champs obligatoires.';
            break;
        }
    }

    if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email n\'est pas valide.';
    }

    if (!isPasswordStrongEnough($motDePasse)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
    } elseif ($motDePasse !== $motDePasseConfirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (empty($erreurs) && UtilisateurModel::findByEmail($valeurs['email']) !== null) {
        $erreurs[] = 'Un compte existe déjà avec cette adresse email.';
    }

    if (empty($erreurs)) {
        $id = UtilisateurModel::create([
            'email'           => $valeurs['email'],
            'mot_de_passe'    => $motDePasse,
            'nom'             => $valeurs['nom'],
            'prenom'          => $valeurs['prenom'],
            'telephone'       => $valeurs['telephone'],
            'adresse_postale' => $valeurs['adresse_postale'],
            'ville'           => $valeurs['ville'],
            'code_postal'     => $valeurs['code_postal'],
            'pays'            => $valeurs['pays'],
        ]);

        MailService::envoyerBienvenue($valeurs['email'], $valeurs['prenom']);

        session_regenerate_id(true);
        $_SESSION['utilisateur_id'] = $id;
        $_SESSION['role'] = 'utilisateur';
        $_SESSION['prenom'] = $valeurs['prenom'];

        flash('success', 'Bienvenue ! Votre compte a été créé avec succès.');
        redirect('/');
    }
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <section class="formulaire-page">
        <h1>Créer un compte</h1>

        <?php if (!empty($erreurs)): ?>
            <ul class="erreurs" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrfField() ?>

            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" value="<?= e($valeurs['prenom']) ?>" required>

            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" required>

            <label for="email">Adresse email *</label>
            <input type="email" id="email" name="email" value="<?= e($valeurs['email']) ?>" required autocomplete="email">

            <label for="telephone">Numéro de mobile *</label>
            <input type="tel" id="telephone" name="telephone" value="<?= e($valeurs['telephone']) ?>" required>

            <label for="adresse_postale">Adresse postale *</label>
            <input type="text" id="adresse_postale" name="adresse_postale" value="<?= e($valeurs['adresse_postale']) ?>" required>

            <label for="code_postal">Code postal *</label>
            <input type="text" id="code_postal" name="code_postal" value="<?= e($valeurs['code_postal']) ?>" required>

            <label for="ville">Ville *</label>
            <input type="text" id="ville" name="ville" value="<?= e($valeurs['ville']) ?>" required>

            <label for="pays">Pays</label>
            <input type="text" id="pays" name="pays" value="<?= e($valeurs['pays']) ?>">

            <label for="mot_de_passe">Mot de passe *</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required
                   aria-describedby="aide-mot-de-passe" autocomplete="new-password">
            <p id="aide-mot-de-passe" class="aide">10 caractères minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.</p>

            <label for="mot_de_passe_confirmation">Confirmer le mot de passe *</label>
            <input type="password" id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" required autocomplete="new-password">

            <button type="submit" class="btn-primary">Créer mon compte</button>
        </form>

        <p>Déjà un compte ? <a href="/connexion.php">Se connecter</a></p>
    </section>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
