<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/UtilisateurModel.php';

$utilisateur = AuthService::requireLogin();
$pageTitle = 'Mes informations personnelles';
$erreurs = [];
$erreursMotDePasse = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profil') {
        $donnees = [
            'nom'             => trim((string) ($_POST['nom'] ?? '')),
            'prenom'          => trim((string) ($_POST['prenom'] ?? '')),
            'telephone'       => trim((string) ($_POST['telephone'] ?? '')),
            'adresse_postale' => trim((string) ($_POST['adresse_postale'] ?? '')),
            'ville'           => trim((string) ($_POST['ville'] ?? '')),
            'code_postal'     => trim((string) ($_POST['code_postal'] ?? '')),
            'pays'            => trim((string) ($_POST['pays'] ?? '')) ?: 'France',
        ];

        foreach (['nom', 'prenom', 'telephone', 'adresse_postale', 'ville', 'code_postal'] as $champ) {
            if ($donnees[$champ] === '') {
                $erreurs[] = 'Merci de renseigner tous les champs obligatoires.';
                break;
            }
        }

        if (empty($erreurs)) {
            UtilisateurModel::updateProfile((int) $utilisateur['utilisateur_id'], $donnees);
            $_SESSION['prenom'] = $donnees['prenom'];
            flash('success', 'Vos informations ont été mises à jour.');
            redirect('/utilisateur/profil.php');
        }
    }

    if ($action === 'mot_de_passe') {
        $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
        $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        $confirmation = (string) ($_POST['nouveau_mot_de_passe_confirmation'] ?? '');

        if (!password_verify($actuel, $utilisateur['mot_de_passe'])) {
            $erreursMotDePasse[] = 'Le mot de passe actuel est incorrect.';
        } elseif (!isPasswordStrongEnough($nouveau)) {
            $erreursMotDePasse[] = 'Le nouveau mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        } elseif ($nouveau !== $confirmation) {
            $erreursMotDePasse[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (empty($erreursMotDePasse)) {
            UtilisateurModel::updatePassword((int) $utilisateur['utilisateur_id'], $nouveau);
            flash('success', 'Votre mot de passe a été modifié.');
            redirect('/utilisateur/profil.php');
        }
    }

    $utilisateur = AuthService::currentUser();
}

require __DIR__ . '/../../src/Views/partials/header.php';
?>
    <h1>Mes informations personnelles</h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert">
            <?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" class="formulaire-page" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="action" value="profil">

        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" value="<?= e($utilisateur['prenom']) ?>" required>

        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" value="<?= e($utilisateur['nom']) ?>" required>

        <label for="telephone">Mobile *</label>
        <input type="tel" id="telephone" name="telephone" value="<?= e($utilisateur['telephone']) ?>" required>

        <label for="adresse_postale">Adresse postale *</label>
        <input type="text" id="adresse_postale" name="adresse_postale" value="<?= e($utilisateur['adresse_postale']) ?>" required>

        <label for="code_postal">Code postal *</label>
        <input type="text" id="code_postal" name="code_postal" value="<?= e($utilisateur['code_postal']) ?>" required>

        <label for="ville">Ville *</label>
        <input type="text" id="ville" name="ville" value="<?= e($utilisateur['ville']) ?>" required>

        <label for="pays">Pays</label>
        <input type="text" id="pays" name="pays" value="<?= e($utilisateur['pays']) ?>">

        <button type="submit" class="btn-primary">Enregistrer</button>
    </form>

    <h2>Changer mon mot de passe</h2>
    <?php if (!empty($erreursMotDePasse)): ?>
        <ul class="erreurs" role="alert">
            <?php foreach ($erreursMotDePasse as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" class="formulaire-page" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mot_de_passe">

        <label for="mot_de_passe_actuel">Mot de passe actuel *</label>
        <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required autocomplete="current-password">

        <label for="nouveau_mot_de_passe">Nouveau mot de passe *</label>
        <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required autocomplete="new-password">

        <label for="nouveau_mot_de_passe_confirmation">Confirmer le nouveau mot de passe *</label>
        <input type="password" id="nouveau_mot_de_passe_confirmation" name="nouveau_mot_de_passe_confirmation" required autocomplete="new-password">

        <button type="submit" class="btn-primary">Changer mon mot de passe</button>
    </form>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
