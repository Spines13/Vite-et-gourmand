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

    <hr class="separateur">

    <section class="atouts" aria-label="Notre savoir-faire">
        <h2>Notre savoir-faire</h2>
        <div class="atouts-grille">
            <div class="atout">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5l2.6 5.3 5.9.8-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.8L12 2.5z"/></svg>
                </span>
                <p>25 ans d'expérience en organisation d'événements culinaires à Bordeaux</p>
            </div>
            <div class="atout">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg>
                </span>
                <p>Des menus composés et actualisés en permanence par notre équipe</p>
            </div>
            <div class="atout">
                <span class="atout-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 7h11v8h-11z"/><path d="M13.5 10h4l3.5 3v2h-7.5z"/><circle cx="6.5" cy="17" r="1.6"/><circle cx="17.5" cy="17" r="1.6"/></svg>
                </span>
                <p>Un accompagnement personnalisé, de la commande à la livraison</p>
            </div>
        </div>
    </section>

    <hr class="separateur">

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
