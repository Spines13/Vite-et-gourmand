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
        <ul>
            <?php foreach ($horaires as $h): ?>
                <li>
                    <?= htmlspecialchars(ucfirst($h['jour'])) ?> :
                    <?= $h['ferme']
                        ? 'Fermé'
                        : htmlspecialchars(substr($h['heure_ouverture'], 0, 5) . ' - ' . substr($h['heure_fermeture'], 0, 5)) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <nav aria-label="Informations légales">
        <a href="/mentions-legales.php">Mentions légales</a>
        <a href="/cgv.php">Conditions générales de vente</a>
    </nav>
</footer>
</body>
</html>
