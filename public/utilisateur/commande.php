<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Services/PricingService.php';
require_once __DIR__ . '/../../src/Services/CommandeAnalyticsService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';
require_once __DIR__ . '/../../src/Models/AvisModel.php';

$utilisateur = AuthService::requireLogin();

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$commande = $id > 0 ? CommandeModel::findByIdPourUtilisateur($id, (int) $utilisateur['utilisateur_id']) : null;

if ($commande === null) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable';
    require __DIR__ . '/../../src/Views/partials/header.php';
    echo '<p>Cette commande n\'existe pas.</p><p><a href="/utilisateur/index.php">Retour à mes commandes</a></p>';
    require __DIR__ . '/../../src/Views/partials/footer.php';
    exit;
}

$pageTitle = 'Commande ' . $commande['numero_commande'];
$erreurs = [];
$modifiable = $commande['statut_code'] === 'en_attente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'annuler' && $modifiable) {
        CommandeModel::annulerParUtilisateur((int) $commande['commande_id'], (int) $commande['menu_id']);
        flash('success', 'Votre commande a été annulée.');
        redirect('/utilisateur/index.php');
    }

    if ($action === 'modifier' && $modifiable) {
        $menu = MenuModel::find((int) $commande['menu_id']);
        $nombrePersonnes = ctype_digit((string) ($_POST['nombre_personnes'] ?? '')) ? (int) $_POST['nombre_personnes'] : 0;
        $dateP = (string) ($_POST['date_prestation'] ?? '');
        $heureL = (string) ($_POST['heure_livraison'] ?? '');
        $adresse = trim((string) ($_POST['adresse_livraison'] ?? ''));
        $ville = trim((string) ($_POST['ville_livraison'] ?? ''));
        $cp = trim((string) ($_POST['code_postal_livraison'] ?? ''));
        $distance = is_numeric($_POST['distance_km'] ?? null) ? (float) $_POST['distance_km'] : 0.0;

        if ($menu !== null && $nombrePersonnes < (int) $menu['nombre_personne_minimum']) {
            $erreurs[] = 'Le nombre de personnes doit être au moins de ' . $menu['nombre_personne_minimum'] . '.';
        }
        $datePrestation = DateTime::createFromFormat('Y-m-d', $dateP);
        if ($datePrestation === false || $datePrestation < new DateTime('today')) {
            $erreurs[] = 'La date de prestation doit être une date valide, à partir d\'aujourd\'hui.';
        }
        if ($adresse === '' || $ville === '' || $cp === '' || $heureL === '') {
            $erreurs[] = 'Merci de renseigner tous les champs obligatoires.';
        }

        if (empty($erreurs) && $menu !== null) {
            $prix = PricingService::calculer($menu, $nombrePersonnes, $ville, $distance);
            CommandeModel::modifierParUtilisateur((int) $commande['commande_id'], [
                'date_prestation'       => $dateP,
                'heure_livraison'       => $heureL,
                'adresse_livraison'     => $adresse,
                'ville_livraison'       => $ville,
                'code_postal_livraison' => $cp,
                'distance_km'           => $distance,
                'frais_livraison'       => $prix['frais_livraison'],
                'nombre_personnes'      => $nombrePersonnes,
                'reduction_pourcentage' => $prix['reduction_pourcentage'],
                'prix_total'            => $prix['prix_total'],
            ]);
            $commandeMaj = CommandeModel::findById((int) $commande['commande_id']);
            CommandeAnalyticsService::synchroniser($commandeMaj);
            flash('success', 'Votre commande a été mise à jour.');
            redirect('/utilisateur/commande.php?id=' . $commande['commande_id']);
        }
    }

    if ($action === 'avis' && $commande['statut_code'] === 'terminee' && AvisModel::findByCommande((int) $commande['commande_id']) === null) {
        $note = ctype_digit((string) ($_POST['note'] ?? '')) ? (int) $_POST['note'] : 0;
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        if ($note < 1 || $note > 5) {
            $erreurs[] = 'La note doit être comprise entre 1 et 5.';
        }
        if ($commentaire === '') {
            $erreurs[] = 'Merci de laisser un commentaire.';
        }

        if (empty($erreurs)) {
            AvisModel::create((int) $commande['commande_id'], (int) $utilisateur['utilisateur_id'], $note, $commentaire);
            flash('success', 'Merci pour votre avis, il sera publié après validation.');
            redirect('/utilisateur/commande.php?id=' . $commande['commande_id']);
        }
    }

    // Recharger la commande a jour apres une tentative en erreur
    $commande = CommandeModel::findByIdPourUtilisateur((int) $commande['commande_id'], (int) $utilisateur['utilisateur_id']);
}

$historique = CommandeModel::historique((int) $commande['commande_id']);
$avis = AvisModel::findByCommande((int) $commande['commande_id']);

require __DIR__ . '/../../src/Views/partials/header.php';
?>
    <p><a href="/utilisateur/index.php">&larr; Retour à mes commandes</a></p>
    <h1>Commande <?= e($commande['numero_commande']) ?></h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <section>
        <p>Menu : <strong><?= e($commande['menu_titre']) ?></strong></p>
        <p>Statut actuel : <strong><?= e($commande['statut_libelle']) ?></strong></p>
        <p>Nombre de personnes : <?= (int) $commande['nombre_personnes'] ?></p>
        <p>Date de prestation : <?= e($commande['date_prestation']) ?> à <?= e(substr($commande['heure_livraison'], 0, 5)) ?></p>
        <p>Livraison : <?= e($commande['adresse_livraison']) ?>, <?= e($commande['code_postal_livraison']) ?> <?= e($commande['ville_livraison']) ?></p>
        <p>Frais de livraison : <?= number_format((float) $commande['frais_livraison'], 2, ',', ' ') ?> €</p>
        <?php if ((float) $commande['reduction_pourcentage'] > 0): ?>
            <p>Réduction appliquée : <?= (float) $commande['reduction_pourcentage'] ?> %</p>
        <?php endif; ?>
        <p><strong>Total : <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €</strong></p>
    </section>

    <?php if ($modifiable): ?>
        <section>
            <h2>Modifier ma commande</h2>
            <p class="aide">Tout est modifiable sauf le choix du menu, tant que la commande n'a pas été acceptée.</p>
            <form method="post" class="formulaire-commande" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="modifier">

                <label for="adresse_livraison">Adresse de livraison *</label>
                <input type="text" id="adresse_livraison" name="adresse_livraison" value="<?= e($commande['adresse_livraison']) ?>" required>

                <label for="code_postal_livraison">Code postal *</label>
                <input type="text" id="code_postal_livraison" name="code_postal_livraison" value="<?= e($commande['code_postal_livraison']) ?>" required>

                <label for="ville_livraison">Ville *</label>
                <input type="text" id="ville_livraison" name="ville_livraison" value="<?= e($commande['ville_livraison']) ?>" required>

                <label for="distance_km">Distance estimée depuis Bordeaux (km)</label>
                <input type="number" id="distance_km" name="distance_km" min="0" step="0.1" value="<?= e((string) $commande['distance_km']) ?>">

                <label for="date_prestation">Date de la prestation *</label>
                <input type="date" id="date_prestation" name="date_prestation" value="<?= e($commande['date_prestation']) ?>" required>

                <label for="heure_livraison">Heure souhaitée de livraison *</label>
                <input type="time" id="heure_livraison" name="heure_livraison" value="<?= e(substr($commande['heure_livraison'], 0, 5)) ?>" required>

                <label for="nombre_personnes">Nombre de personnes *</label>
                <input type="number" id="nombre_personnes" name="nombre_personnes" min="<?= (int) $commande['nombre_personne_minimum'] ?>" value="<?= (int) $commande['nombre_personnes'] ?>" required>

                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            </form>

            <form method="post" onsubmit="return confirm('Confirmez-vous l\'annulation de cette commande ?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="annuler">
                <button type="submit" class="btn-secondary">Annuler la commande</button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (!empty($historique)): ?>
        <section>
            <h2>Suivi de la commande</h2>
            <ul class="suivi-commande">
                <?php foreach ($historique as $etape): ?>
                    <li><?= e($etape['statut_libelle']) ?> — <?= e($etape['date_heure']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($commande['statut_code'] === 'terminee'): ?>
        <section>
            <h2>Votre avis</h2>
            <?php if ($avis !== null): ?>
                <p>Vous avez déjà laissé un avis (<?= (int) $avis['note'] ?>/5) — statut : <?= e($avis['statut']) ?>.</p>
            <?php else: ?>
                <form method="post" class="formulaire-page" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="avis">

                    <label for="note">Note *</label>
                    <select id="note" name="note" required>
                        <option value="">--</option>
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                            <option value="<?= $n ?>"><?= $n ?> / 5</option>
                        <?php endfor; ?>
                    </select>

                    <label for="commentaire">Commentaire *</label>
                    <textarea id="commentaire" name="commentaire" rows="4" required></textarea>

                    <button type="submit" class="btn-primary">Envoyer mon avis</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
