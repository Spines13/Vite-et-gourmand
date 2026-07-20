<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
$pageTitle = 'Conditions générales de vente';
require __DIR__ . '/../src/Views/partials/header.php';
?>
    <h1>Conditions générales de vente</h1>

    <h2>Article 1 — Objet</h2>
    <p>
        Les présentes conditions générales de vente régissent les commandes de menus événementiels
        passées auprès de Vite &amp; Gourmand via le site internet.
    </p>

    <h2>Article 2 — Commande</h2>
    <p>
        Toute commande est soumise au nombre de personnes minimum indiqué sur chaque menu. Les
        conditions spécifiques à chaque menu (délai minimum de commande, précautions de stockage)
        sont affichées sur la page de détail du menu et doivent être respectées.
    </p>

    <h2>Article 3 — Prix et réduction</h2>
    <p>
        Le prix affiché correspond au nombre de personnes minimum du menu. Il est recalculé
        proportionnellement au nombre de personnes choisi. Une réduction de 10 % est appliquée
        automatiquement à toute commande dont le nombre de personnes est supérieur ou égal au
        nombre minimum du menu augmenté de 5 personnes.
    </p>

    <h2>Article 4 — Frais de livraison</h2>
    <p>
        La livraison est gratuite dans la ville de Bordeaux. Pour toute livraison en dehors de
        Bordeaux, des frais de 5 € majorés de 0,59 € par kilomètre parcouru sont facturés en sus
        du prix du menu.
    </p>

    <h2>Article 5 — Annulation et modification</h2>
    <p>
        Le client peut annuler ou modifier librement sa commande tant qu'elle n'a pas été acceptée
        par notre équipe. Une fois la commande acceptée, toute annulation ou modification à
        l'initiative de Vite &amp; Gourmand ne peut intervenir qu'après prise de contact avec le
        client (par téléphone ou par email).
    </p>

    <h2>Article 6 — Matériel prêté</h2>
    <p>
        Lorsque du matériel est prêté au client dans le cadre de sa prestation, celui-ci doit être
        restitué à Vite &amp; Gourmand. Un email de rappel est envoyé au client dès la livraison.
        <strong>Passé un délai de 10 jours ouvrés</strong> sans restitution du matériel, des frais
        de <strong>600 €</strong> seront facturés au client.
    </p>

    <h2>Article 7 — Avis clients</h2>
    <p>
        À l'issue d'une commande terminée, le client peut laisser une note (1 à 5) et un
        commentaire. Les avis sont soumis à modération avant publication sur le site.
    </p>

    <h2>Article 8 — Contact</h2>
    <p>
        Pour toute question relative à ces conditions générales de vente, vous pouvez nous
        contacter depuis la page <a href="/contact.php">contact</a>.
    </p>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
