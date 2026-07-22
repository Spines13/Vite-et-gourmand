<?php
$horaires = getPDO()->query(
    "SELECT jour, heure_ouverture, heure_fermeture, ferme FROM horaire
     ORDER BY FIELD(jour, 'lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche')"
)->fetchAll();
?>
</main>
<footer>
    <section aria-label="Horaires d'ouverture">
        <h2>Horaires</h2>
        <ul class="horaires-grille">
            <?php foreach ($horaires as $h): ?>
                <li>
                    <span class="horaires-jour"><?= htmlspecialchars(ucfirst($h['jour'])) ?></span>
                    <span class="horaires-heures">
                        <?= $h['ferme']
                            ? 'Fermé'
                            : htmlspecialchars(substr($h['heure_ouverture'], 0, 5) . ' à ' . substr($h['heure_fermeture'], 0, 5)) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <nav aria-label="Informations légales">
        <a href="/mentions-legales.php">Mentions légales</a>
        <a href="/cgv.php">Conditions générales de vente</a>
    </nav>
    <p class="copyright">&copy; 2026 Vite &amp; Gourmand</p>
</footer>
</body>
</html>
