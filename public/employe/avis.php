<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Services/AuthService.php';
require_once __DIR__ . '/../../src/Models/AvisModel.php';

AuthService::requireRole('employe', 'administrateur');
$pageTitle = 'Modération des avis';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $avisId = (int) ($_POST['avis_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($avisId > 0 && in_array($action, ['valider', 'refuser'], true)) {
        AvisModel::moderer($avisId, $action === 'valider');
        flash('success', 'L\'avis a été ' . ($action === 'valider' ? 'validé' : 'refusé') . '.');
    }
    redirect('/employe/avis.php');
}

$avisEnAttente = AvisModel::listEnAttente();

require __DIR__ . '/../../src/Views/partials/header.php';
require __DIR__ . '/../../src/Views/partials/nav-employe.php';
?>
    <h1>Modération des avis</h1>

    <?php if (empty($avisEnAttente)): ?>
        <p>Aucun avis en attente de modération.</p>
    <?php else: ?>
        <?php foreach ($avisEnAttente as $avis): ?>
            <article class="menu-card-body" style="background:#fff;border:1px solid #e5ddd0;border-radius:6px;padding:1rem;margin-bottom:1rem;">
                <p><?= str_repeat('★', (int) $avis['note']) . str_repeat('☆', 5 - (int) $avis['note']) ?> — <?= e($avis['prenom'] . ' ' . $avis['nom']) ?> (commande <?= e($avis['numero_commande']) ?>)</p>
                <blockquote><?= nl2br(e($avis['commentaire'])) ?></blockquote>
                <form method="post" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="avis_id" value="<?= (int) $avis['avis_id'] ?>">
                    <input type="hidden" name="action" value="valider">
                    <button type="submit" class="btn-primary">Valider</button>
                </form>
                <form method="post" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="avis_id" value="<?= (int) $avis['avis_id'] ?>">
                    <input type="hidden" name="action" value="refuser">
                    <button type="submit" class="btn-secondary">Refuser</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
require __DIR__ . '/../../src/Views/partials/footer.php';
