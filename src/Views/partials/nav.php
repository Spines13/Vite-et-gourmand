<?php
$cheminActuel = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

$estActif = static function (string $chemin, bool $exact = true) use ($cheminActuel): string {
    $actif = $exact ? $cheminActuel === $chemin : str_starts_with($cheminActuel, $chemin);
    return $actif ? ' aria-current="page" class="actif"' : '';
};
?>
<nav class="navbar" aria-label="Navigation principale">
    <a class="navbar-brand" href="/">Vite &amp; Gourmand</a>
    <ul class="navbar-links">
        <li><a href="/"<?= $estActif('/') ?>>Accueil</a></li>
        <li><a href="/menus.php"<?= $estActif('/menus.php', false) ?>>Nos menus</a></li>
        <li><a href="/contact.php"<?= $estActif('/contact.php') ?>>Contact</a></li>
        <?php if (!empty($_SESSION['utilisateur_id'])): ?>
            <?php if (($_SESSION['role'] ?? '') === 'administrateur'): ?>
                <li><a href="/admin/index.php"<?= $estActif('/admin/', false) ?>>Espace administrateur</a></li>
            <?php elseif (($_SESSION['role'] ?? '') === 'employe'): ?>
                <li><a href="/employe/index.php"<?= $estActif('/employe/', false) ?>>Espace employé</a></li>
            <?php else: ?>
                <li><a href="/utilisateur/index.php"<?= $estActif('/utilisateur/', false) ?>>Mon espace</a></li>
            <?php endif; ?>
            <li><a href="/deconnexion.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="/connexion.php"<?= $estActif('/connexion.php') ?>>Connexion</a></li>
        <?php endif; ?>
    </ul>
</nav>
