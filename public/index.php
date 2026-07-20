<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';

$pageTitle = 'Accueil';

$avisValides = getPDO()->query(
    "SELECT a.note, a.commentaire, u.prenom, c.numero_commande
     FROM avis a
     JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id
     JOIN commande c ON c.commande_id = a.commande_id
     WHERE a.statut = 'valide'
     ORDER BY a.date_moderation DESC
     LIMIT 6"
)->fetchAll();

require __DIR__ . '/../src/Views/partials/header.php';
?>
    <section class="hero">
        <h1>Vite &amp; Gourmand</h1>
        <p>
            Traiteur événementiel à Bordeaux depuis 25 ans, Julie et José composent pour vous
            des menus de saison pour toutes vos réceptions : Noël, Pâques ou tout événement
            que vous souhaitez célébrer.
        </p>
        <a class="btn-primary" href="/menus.php">Découvrir nos menus</a>
    </section>

    <section class="atouts" aria-label="Notre savoir-faire">
        <h2>Notre savoir-faire</h2>
        <ul>
            <li>25 ans d'expérience en organisation d'événements culinaires à Bordeaux</li>
            <li>Des menus composés et actualisés en permanence par notre équipe</li>
            <li>Un accompagnement personnalisé, de la commande à la livraison</li>
        </ul>
    </section>

    <section class="avis" aria-label="Avis de nos clients">
        <h2>Ils nous font confiance</h2>
        <?php if (empty($avisValides)): ?>
            <p>Les avis de nos clients seront bientôt disponibles.</p>
        <?php else: ?>
            <ul class="avis-liste">
                <?php foreach ($avisValides as $avis): ?>
                    <li class="avis-item">
                        <p class="avis-note" aria-label="Note : <?= (int) $avis['note'] ?> sur 5">
                            <?= str_repeat('★', (int) $avis['note']) . str_repeat('☆', 5 - (int) $avis['note']) ?>
                        </p>
                        <blockquote>
                            <?= nl2br(htmlspecialchars($avis['commentaire'])) ?>
                        </blockquote>
                        <p class="avis-auteur">— <?= htmlspecialchars($avis['prenom']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
