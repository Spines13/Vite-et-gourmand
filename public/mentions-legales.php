<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Config/bootstrap.php';
$pageTitle = 'Mentions légales';
require __DIR__ . '/../src/Views/partials/header.php';
?>
    <h1>Mentions légales</h1>

    <h2>Éditeur du site</h2>
    <p>
        Vite &amp; Gourmand — entreprise individuelle<br>
        Adresse : [adresse à compléter], Bordeaux<br>
        SIRET : [à compléter]<br>
        Email : <?= e(env('CONTACT_EMAIL', 'contact@vite-et-gourmand.fr')) ?><br>
        Directrice de la publication : Julie
    </p>

    <h2>Hébergement</h2>
    <p>[Nom et adresse de l'hébergeur à compléter lors du déploiement]</p>

    <h2>Protection des données personnelles (RGPD)</h2>
    <p>
        Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi
        Informatique et Libertés, Vite &amp; Gourmand collecte et traite les données personnelles
        suivantes dans le cadre de la création de compte et de la gestion des commandes :
        nom, prénom, adresse email, numéro de téléphone, adresse postale.
    </p>
    <ul>
        <li><strong>Finalité :</strong> gestion des comptes clients, traitement des commandes, communication liée aux commandes.</li>
        <li><strong>Base légale :</strong> exécution du contrat de vente et consentement pour la création de compte.</li>
        <li><strong>Destinataires :</strong> uniquement le personnel habilité de Vite &amp; Gourmand.</li>
        <li><strong>Durée de conservation :</strong> pendant la durée de la relation commerciale, puis archivage conformément aux obligations légales.</li>
        <li><strong>Sécurité :</strong> mots de passe stockés sous forme hachée, connexion soumise à authentification, accès restreint selon le rôle de l'utilisateur.</li>
    </ul>
    <p>
        Conformément à la réglementation, vous disposez d'un droit d'accès, de rectification,
        d'effacement et de portabilité de vos données, ainsi que d'un droit d'opposition et de
        limitation du traitement. Vous pouvez exercer ces droits en nous contactant à l'adresse
        <?= e(env('CONTACT_EMAIL', 'contact@vite-et-gourmand.fr')) ?> ou depuis la page
        <a href="/contact.php">contact</a>.
    </p>

    <h2>Cookies</h2>
    <p>
        Le site utilise uniquement un cookie de session, strictement nécessaire au fonctionnement
        du site (maintien de la connexion). Aucun cookie de mesure d'audience ou publicitaire
        n'est utilisé.
    </p>
<?php
require __DIR__ . '/../src/Views/partials/footer.php';
