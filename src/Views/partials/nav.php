<nav class="navbar" aria-label="Navigation principale">
    <a class="navbar-brand" href="/">Vite &amp; Gourmand</a>
    <ul class="navbar-links">
        <li><a href="/">Accueil</a></li>
        <li><a href="/menus.php">Nos menus</a></li>
        <li><a href="/contact.php">Contact</a></li>
        <?php if (!empty($_SESSION['utilisateur_id'])): ?>
            <?php if (($_SESSION['role'] ?? '') === 'administrateur'): ?>
                <li><a href="/admin/index.php">Espace administrateur</a></li>
            <?php elseif (($_SESSION['role'] ?? '') === 'employe'): ?>
                <li><a href="/employe/index.php">Espace employé</a></li>
            <?php else: ?>
                <li><a href="/utilisateur/index.php">Mon espace</a></li>
            <?php endif; ?>
            <li><a href="/deconnexion.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="/connexion.php">Connexion</a></li>
        <?php endif; ?>
    </ul>
</nav>
