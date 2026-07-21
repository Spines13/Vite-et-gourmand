<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Services/MailService.php';
require_once __DIR__ . '/../../src/Services/CommandeAnalyticsService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';
require_once __DIR__ . '/../../src/Models/UtilisateurModel.php';

AuthService::requireRole('employe', 'administrateur');

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$commande = $id > 0 ? CommandeModel::findById($id) : null;

if ($commande === null) {
    http_response_code(404);
    exit('Commande introuvable.');
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');
    $client = UtilisateurModel::findById((int) $commande['utilisateur_id']);

    if ($action === 'definir_materiel') {
        CommandeModel::setPretMateriel((int) $commande['commande_id'], isset($_POST['pret_materiel']));
        flash('success', 'Information mise à jour.');
        redirect('/employe/commande.php?id=' . $commande['commande_id']);
    }

    if ($action === 'avancer_statut') {
        $ancienStatut = $commande['statut_code'];
        $commande = CommandeModel::avancerStatut((int) $commande['commande_id']);
        CommandeAnalyticsService::synchroniser($commande);

        if ($commande['statut_code'] === 'en_attente_retour_materiel') {
            MailService::envoyerRetourMaterielRequis($client['email'], $client['prenom'], $commande);
        } else {
            MailService::envoyerChangementStatut($client['email'], $client['prenom'], $commande, $commande['statut_libelle']);
        }
        if ($commande['statut_code'] === 'terminee') {
            MailService::envoyerInvitationAvis($client['email'], $client['prenom'], $commande);
        }

        flash('success', 'Le statut de la commande a été mis à jour.');
        redirect('/employe/commande.php?id=' . $commande['commande_id']);
    }

    if ($action === 'confirmer_retour_materiel') {
        $commande = CommandeModel::confirmerRetourMateriel((int) $commande['commande_id']);
        CommandeAnalyticsService::synchroniser($commande);
        MailService::envoyerChangementStatut($client['email'], $client['prenom'], $commande, $commande['statut_libelle']);
        flash('success', 'Le retour de matériel a été confirmé, la commande est terminée.');
        redirect('/employe/commande.php?id=' . $commande['commande_id']);
    }

    if ($action === 'annuler') {
        $motif = trim((string) ($_POST['motif_annulation'] ?? ''));
        $modeContact = (string) ($_POST['mode_contact_annulation'] ?? '');

        if ($motif === '') {
            $erreurs[] = 'Merci de préciser le motif de l\'annulation.';
        }
        if (!in_array($modeContact, ['telephone', 'mail'], true)) {
            $erreurs[] = 'Merci de préciser comment le client a été contacté.';
        }

        if (empty($erreurs)) {
            $commande = CommandeModel::annulerParEmploye((int) $commande['commande_id'], (int) $commande['menu_id'], $motif, $modeContact);
            CommandeAnalyticsService::synchroniser($commande);
            MailService::envoyerChangementStatut($client['email'], $client['prenom'], $commande, $commande['statut_libelle']);
            flash('success', 'La commande a été annulée.');
            redirect('/employe/commande.php?id=' . $commande['commande_id']);
        }
    }
}

$pageTitle = 'Commande ' . $commande['numero_commande'];
$historique = CommandeModel::historique((int) $commande['commande_id']);
$prochainCode = CommandeModel::prochainStatutCode($commande);
$statutsLibelles = getPDO()->query('SELECT code, libelle FROM statut_commande')->fetchAll(PDO::FETCH_KEY_PAIR);
$peutEvoluer = !in_array($commande['statut_code'], ['terminee', 'annulee'], true);

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-espace.php';
?>
    <p><a href="/employe/commandes.php">&larr; Retour aux commandes</a></p>
    <h1>Commande <?= e($commande['numero_commande']) ?></h1>

    <?php if (!empty($erreurs)): ?>
        <ul class="erreurs" role="alert"><?php foreach ($erreurs as $erreur): ?><li><?= e($erreur) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <section>
        <p>Menu : <strong><?= e($commande['menu_titre']) ?></strong> — <?= (int) $commande['nombre_personnes'] ?> personnes</p>
        <p>Statut actuel : <strong><?= e($commande['statut_libelle']) ?></strong></p>
        <p>Date de prestation : <?= e($commande['date_prestation']) ?> à <?= e(substr($commande['heure_livraison'], 0, 5)) ?></p>
        <p>Livraison : <?= e($commande['adresse_livraison']) ?>, <?= e($commande['code_postal_livraison']) ?> <?= e($commande['ville_livraison']) ?></p>
        <p>Total : <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €</p>
        <?php if (!empty($commande['motif_annulation'])): ?>
            <p>Motif d'annulation : <?= e($commande['motif_annulation']) ?> (contact : <?= e($commande['mode_contact_annulation']) ?>)</p>
        <?php endif; ?>
    </section>

    <?php if ($peutEvoluer): ?>
        <section>
            <h2>Matériel prêté</h2>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="definir_materiel">
                <label>
                    <input type="checkbox" name="pret_materiel" onchange="this.form.submit()" <?= $commande['pret_materiel'] ? 'checked' : '' ?>>
                    Du matériel a été prêté pour cette commande
                </label>
            </form>

            <h2>Faire avancer le statut</h2>
            <?php if ($prochainCode !== null): ?>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="avancer_statut">
                    <button type="submit" class="btn-primary">Passer au statut « <?= e($statutsLibelles[$prochainCode]) ?> »</button>
                </form>
            <?php endif; ?>

            <?php if ($commande['statut_code'] === 'en_attente_retour_materiel'): ?>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="confirmer_retour_materiel">
                    <button type="submit" class="btn-primary">Confirmer la restitution du matériel</button>
                </form>
            <?php endif; ?>

            <h2>Annuler la commande</h2>
            <p class="aide">L'annulation n'est possible qu'après avoir contacté le client.</p>
            <form method="post" class="formulaire-commande" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="annuler">

                <label for="mode_contact_annulation">Client contacté par *</label>
                <select id="mode_contact_annulation" name="mode_contact_annulation" required>
                    <option value="">--</option>
                    <option value="telephone">Téléphone</option>
                    <option value="mail">Email</option>
                </select>

                <label for="motif_annulation">Motif de l'annulation *</label>
                <textarea id="motif_annulation" name="motif_annulation" rows="3" required></textarea>

                <button type="submit" class="btn-secondary">Annuler la commande</button>
            </form>
        </section>
    <?php endif; ?>

    <?php if (!empty($historique)): ?>
        <section>
            <h2>Historique</h2>
            <ul class="suivi-commande">
                <?php foreach ($historique as $etape): ?>
                    <li><?= e($etape['statut_libelle']) ?> — <?= e($etape['date_heure']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
