<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../src/Services/PricingService.php';
require_once __DIR__ . '/../src/Services/CommandeAnalyticsService.php';
require_once __DIR__ . '/../src/Models/MenuModel.php';
require_once __DIR__ . '/../src/Models/CommandeModel.php';

$utilisateur = AuthService::requireLogin();
$pageTitle = 'Commander';

$menusDisponibles = MenuModel::listActive();
$erreurs = [];

$menuIdChoisi = isset($_REQUEST['menu_id']) && ctype_digit((string) $_REQUEST['menu_id']) ? (int) $_REQUEST['menu_id'] : 0;

$valeurs = [
    'menu_id'               => $menuIdChoisi,
    'nom'                   => $utilisateur['nom'],
    'prenom'                => $utilisateur['prenom'],
    'email'                 => $utilisateur['email'],
    'telephone'             => $utilisateur['telephone'],
    'adresse_livraison'     => $utilisateur['adresse_postale'],
    'ville_livraison'       => $utilisateur['ville'],
    'code_postal_livraison' => $utilisateur['code_postal'],
    'date_prestation'       => '',
    'heure_livraison'       => '',
    'distance_km'           => '0',
    'nombre_personnes'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach ($valeurs as $champ => $defaut) {
        if ($champ !== 'menu_id') {
            $valeurs[$champ] = trim((string) ($_POST[$champ] ?? ''));
        }
    }
    $valeurs['menu_id'] = isset($_POST['menu_id']) && ctype_digit((string) $_POST['menu_id']) ? (int) $_POST['menu_id'] : 0;

    $menu = $valeurs['menu_id'] > 0 ? MenuModel::find($valeurs['menu_id']) : null;

    if ($menu === null) {
        $erreurs[] = 'Merci de choisir un menu valide.';
    } elseif ((int) $menu['stock_disponible'] <= 0) {
        $erreurs[] = 'Ce menu est en rupture de stock.';
    }

    foreach (['nom', 'prenom', 'email', 'telephone', 'adresse_livraison', 'ville_livraison', 'code_postal_livraison', 'date_prestation', 'heure_livraison', 'nombre_personnes'] as $champObligatoire) {
        if ($valeurs[$champObligatoire] === '') {
            $erreurs[] = 'Merci de renseigner tous les champs obligatoires.';
            break;
        }
    }

    $nombrePersonnes = ctype_digit($valeurs['nombre_personnes']) ? (int) $valeurs['nombre_personnes'] : 0;
    if ($menu !== null && $nombrePersonnes < (int) $menu['nombre_personne_minimum']) {
        $erreurs[] = 'Le nombre de personnes doit être au moins de ' . $menu['nombre_personne_minimum'] . ' pour ce menu.';
    }

    $datePrestation = DateTime::createFromFormat('Y-m-d', $valeurs['date_prestation']);
    if ($datePrestation === false || $datePrestation < new DateTime('today')) {
        $erreurs[] = 'La date de prestation doit être une date valide, à partir d\'aujourd\'hui.';
    }

    $distanceKm = is_numeric($valeurs['distance_km']) ? (float) $valeurs['distance_km'] : 0.0;

    if (empty($erreurs)) {
        $prix = PricingService::calculer($menu, $nombrePersonnes, $valeurs['ville_livraison'], $distanceKm);

        $commande = CommandeModel::create([
            'utilisateur_id'        => $utilisateur['utilisateur_id'],
            'menu_id'               => $menu['menu_id'],
            'date_prestation'       => $valeurs['date_prestation'],
            'heure_livraison'       => $valeurs['heure_livraison'],
            'adresse_livraison'     => $valeurs['adresse_livraison'],
            'ville_livraison'       => $valeurs['ville_livraison'],
            'code_postal_livraison' => $valeurs['code_postal_livraison'],
            'distance_km'           => $distanceKm,
            'frais_livraison'       => $prix['frais_livraison'],
            'nombre_personnes'      => $nombrePersonnes,
            'prix_menu_unitaire'    => $prix['prix_unitaire'],
            'reduction_pourcentage' => $prix['reduction_pourcentage'],
            'prix_total'            => $prix['prix_total'],
        ]);

        CommandeAnalyticsService::synchroniser($commande);
        MailService::envoyerConfirmationCommande($valeurs['email'], $valeurs['prenom'], $commande);

        flash('success', 'Votre commande ' . $commande['numero_commande'] . ' a bien été enregistrée.');
        redirect('/commande-confirmation.php?numero=' . urlencode($commande['numero_commande']));
    }
}

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <h1>Commander</h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" class="formulaire-commande" novalidate>
        <?= csrfField() ?>

        <fieldset>
            <legend>Menu</legend>
            <label for="menu_id">Menu choisi *</label>
            <select id="menu_id" name="menu_id" required>
                <option value="">-- Choisir un menu --</option>
                <?php foreach ($menusDisponibles as $menu): ?>
                    <option value="<?= (int) $menu['menu_id'] ?>"
                        data-prix="<?= (float) $menu['prix_personne_minimum'] ?>"
                        data-min="<?= (int) $menu['nombre_personne_minimum'] ?>"
                        <?= (int) $menu['stock_disponible'] <= 0 ? 'disabled' : '' ?>
                        <?= $valeurs['menu_id'] === (int) $menu['menu_id'] ? 'selected' : '' ?>>
                        <?= e($menu['titre']) ?> (<?= (int) $menu['nombre_personne_minimum'] ?> pers. min. — <?= number_format((float) $menu['prix_personne_minimum'], 2, ',', ' ') ?> €)
                        <?= (int) $menu['stock_disponible'] <= 0 ? ' - rupture de stock' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <fieldset>
            <legend>Vos coordonnées</legend>

            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" value="<?= e($valeurs['prenom']) ?>" required>

            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" value="<?= e($valeurs['nom']) ?>" required>

            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="<?= e($valeurs['email']) ?>" required>

            <label for="telephone">Mobile *</label>
            <input type="tel" id="telephone" name="telephone" value="<?= e($valeurs['telephone']) ?>" required>
        </fieldset>

        <fieldset>
            <legend>Livraison</legend>

            <label for="adresse_livraison">Adresse de livraison *</label>
            <input type="text" id="adresse_livraison" name="adresse_livraison" value="<?= e($valeurs['adresse_livraison']) ?>" required>

            <label for="code_postal_livraison">Code postal *</label>
            <input type="text" id="code_postal_livraison" name="code_postal_livraison" value="<?= e($valeurs['code_postal_livraison']) ?>" required>

            <label for="ville_livraison">Ville *</label>
            <input type="text" id="ville_livraison" name="ville_livraison" value="<?= e($valeurs['ville_livraison']) ?>" required>

            <div id="champ-distance">
                <label for="distance_km">Distance estimée depuis Bordeaux (km)</label>
                <input type="number" id="distance_km" name="distance_km" min="0" step="0.1" value="<?= e($valeurs['distance_km']) ?>">
                <p class="aide">Une livraison hors de Bordeaux est facturée 5 € + 0,59 €/km. Cette estimation sera vérifiée par notre équipe.</p>
            </div>

            <label for="date_prestation">Date de la prestation *</label>
            <input type="date" id="date_prestation" name="date_prestation" value="<?= e($valeurs['date_prestation']) ?>" required>

            <label for="heure_livraison">Heure souhaitée de livraison *</label>
            <input type="time" id="heure_livraison" name="heure_livraison" value="<?= e($valeurs['heure_livraison']) ?>" required>
        </fieldset>

        <fieldset>
            <legend>Nombre de personnes</legend>
            <label for="nombre_personnes">Nombre de personnes *</label>
            <input type="number" id="nombre_personnes" name="nombre_personnes" min="1" value="<?= e($valeurs['nombre_personnes']) ?>" required>
            <p class="aide">Une réduction de 10 % est appliquée à partir de 5 personnes supplémentaires par rapport au minimum du menu.</p>
        </fieldset>

        <div id="recapitulatif-prix" class="recapitulatif-prix" aria-live="polite">
            <h2>Récapitulatif</h2>
            <p>Prix du menu : <span id="recap-prix-menu">-</span></p>
            <p>Réduction : <span id="recap-reduction">-</span></p>
            <p>Frais de livraison : <span id="recap-frais-livraison">-</span></p>
            <p><strong>Total : <span id="recap-prix-total">-</span></strong></p>
        </div>

        <button type="submit" class="btn-primary">Valider ma commande</button>
    </form>

    <script src="/assets/js/commande.js" defer></script>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
