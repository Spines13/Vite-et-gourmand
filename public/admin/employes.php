<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Services/MailService.php';
require_once __DIR__ . '/../../src/Models/UtilisateurModel.php';

AuthService::requireRole('administrateur');
$pageTitle = 'Gestion des employés';
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'basculer_actif') {
        $id = (int) ($_POST['utilisateur_id'] ?? 0);
        $employe = UtilisateurModel::findById($id);
        if ($employe !== null && $employe['role_code'] === 'employe') {
            UtilisateurModel::setActif($id, !((bool) $employe['actif']));
            flash('success', 'Le compte a été mis à jour.');
        }
        redirect('/admin/employes.php');
    }

    if ($action === 'creer') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse email n\'est pas valide.';
        }
        if ($nom === '' || $prenom === '') {
            $erreurs[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!isPasswordStrongEnough($motDePasse)) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }
        if (empty($erreurs) && UtilisateurModel::findByEmail($email) !== null) {
            $erreurs[] = 'Un compte existe déjà avec cette adresse email.';
        }

        if (empty($erreurs)) {
            UtilisateurModel::createEmploye($email, $motDePasse, $nom, $prenom);
            MailService::envoyerCreationCompteEmploye($email, $prenom);
            flash('success', 'Le compte employé a été créé. Le mot de passe doit être communiqué de vive voix, il n\'a pas été envoyé par email.');
            redirect('/admin/employes.php');
        }
    }
}

$employes = UtilisateurModel::listEmployes();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-admin.php';
?>
    <h1>Gestion des employés</h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <table class="table-commandes">
        <thead><tr><th scope="col">Nom</th><th scope="col">Email</th><th scope="col">Statut</th><th scope="col"></th></tr></thead>
        <tbody>
            <?php foreach ($employes as $employe): ?>
                <tr>
                    <td><?= e($employe['prenom'] . ' ' . $employe['nom']) ?></td>
                    <td><?= e($employe['email']) ?></td>
                    <td><?= (bool) $employe['actif'] ? 'Actif' : 'Désactivé' ?></td>
                    <td>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="basculer_actif">
                            <input type="hidden" name="utilisateur_id" value="<?= (int) $employe['utilisateur_id'] ?>">
                            <button type="submit" class="btn-secondary"><?= (bool) $employe['actif'] ? 'Désactiver' : 'Réactiver' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($employes)): ?>
                <tr><td colspan="4">Aucun compte employé pour le moment.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Créer un compte employé</h2>
    <p class="aide">Le mot de passe n'est jamais envoyé par email : l'employé doit se rapprocher de vous pour l'obtenir.</p>
    <form method="post" class="formulaire-page" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="action" value="creer">

        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" required>

        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" required>

        <label for="email">Email (identifiant de connexion) *</label>
        <input type="email" id="email" name="email" required>

        <label for="mot_de_passe">Mot de passe *</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required aria-describedby="aide-mdp">
        <p id="aide-mdp" class="aide">10 caractères minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.</p>

        <button type="submit" class="btn-primary">Créer le compte</button>
    </form>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
