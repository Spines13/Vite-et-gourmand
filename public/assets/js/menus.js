(function () {
    'use strict';

    var form = document.getElementById('filtres-menus');
    var resultats = document.getElementById('menus-resultats');
    var statut = document.getElementById('menus-statut');
    if (!form || !resultats) {
        return;
    }

    function echapper(texte) {
        var div = document.createElement('div');
        div.textContent = texte || '';
        return div.innerHTML;
    }

    function tronquer(texte, longueur) {
        if (!texte) return '';
        return texte.length > longueur ? texte.slice(0, longueur) + '…' : texte;
    }

    function carteMenu(menu) {
        var rupture = !menu.disponible
            ? '<p class="menu-card-rupture">Rupture de stock</p>'
            : '';
        var image = menu.image ? '/' + menu.image : '/assets/img/menus/placeholder.jpg';
        return (
            '<article class="menu-card">' +
            '<img src="' + echapper(image) + '" alt="" loading="lazy">' +
            '<div class="menu-card-body">' +
            '<p class="menu-card-theme">' + echapper(menu.theme) + '</p>' +
            '<h3>' + echapper(menu.titre) + '</h3>' +
            '<p class="menu-card-description">' + echapper(tronquer(menu.description, 140)) + '</p>' +
            '<p class="menu-card-infos">À partir de ' + menu.nombre_personne_minimum + ' personnes — <strong>' +
            menu.prix_personne_minimum.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €</strong></p>' +
            rupture +
            '<a class="btn-secondary" href="/menu-detail.php?id=' + menu.id + '">Voir le détail</a>' +
            '</div></article>'
        );
    }

    function actualiserMenus() {
        var params = new URLSearchParams(new FormData(form));
        // Retire les champs vides pour garder une requete propre
        Array.from(params.keys()).forEach(function (cle) {
            if (params.get(cle) === '') {
                params.delete(cle);
            }
        });

        fetch('/api/menus.php?' + params.toString())
            .then(function (reponse) { return reponse.json(); })
            .then(function (donnees) {
                resultats.innerHTML = donnees.menus.length
                    ? donnees.menus.map(carteMenu).join('')
                    : '<p>Aucun menu ne correspond à ces critères pour le moment.</p>';
                if (statut) {
                    statut.textContent = donnees.menus.length + ' menu(s) trouvé(s).';
                }
            })
            .catch(function () {
                if (statut) {
                    statut.textContent = 'Une erreur est survenue lors de la mise à jour des menus.';
                }
            });
    }

    form.addEventListener('submit', function (evenement) {
        evenement.preventDefault();
        actualiserMenus();
    });

    form.querySelectorAll('input, select').forEach(function (champ) {
        champ.addEventListener('change', actualiserMenus);
    });
})();
