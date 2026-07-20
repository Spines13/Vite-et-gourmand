(function () {
    'use strict';

    var selectMenu = document.getElementById('menu_id');
    var champPersonnes = document.getElementById('nombre_personnes');
    var champVille = document.getElementById('ville_livraison');
    var champDistance = document.getElementById('distance_km');

    if (!selectMenu || !champPersonnes) {
        return;
    }

    function formaterEuros(valeur) {
        return Number(valeur).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €';
    }

    function appliquerMinimumPersonnes() {
        var option = selectMenu.options[selectMenu.selectedIndex];
        var min = option ? parseInt(option.getAttribute('data-min'), 10) : 1;
        if (!min) return;
        champPersonnes.min = min;
        if (!champPersonnes.value || parseInt(champPersonnes.value, 10) < min) {
            champPersonnes.value = min;
        }
    }

    function actualiserRecapitulatif() {
        var menuId = selectMenu.value;
        var recap = document.getElementById('recapitulatif-prix');
        if (!menuId || !champPersonnes.value) {
            return;
        }

        var params = new URLSearchParams({
            menu_id: menuId,
            nombre_personnes: champPersonnes.value,
            ville_livraison: champVille ? champVille.value : '',
            distance_km: champDistance ? (champDistance.value || '0') : '0'
        });

        fetch('/api/prix-commande.php?' + params.toString())
            .then(function (reponse) { return reponse.json(); })
            .then(function (prix) {
                if (prix.erreur) {
                    return;
                }
                document.getElementById('recap-prix-menu').textContent = formaterEuros(prix.prix_menu);
                document.getElementById('recap-reduction').textContent = prix.reduction_pourcentage > 0
                    ? '-' + prix.reduction_pourcentage + ' %'
                    : 'Aucune';
                document.getElementById('recap-frais-livraison').textContent = formaterEuros(prix.frais_livraison);
                document.getElementById('recap-prix-total').textContent = formaterEuros(prix.prix_total);
            });
    }

    selectMenu.addEventListener('change', function () {
        appliquerMinimumPersonnes();
        actualiserRecapitulatif();
    });

    [champPersonnes, champVille, champDistance].forEach(function (champ) {
        if (champ) {
            champ.addEventListener('input', actualiserRecapitulatif);
        }
    });

    appliquerMinimumPersonnes();
    actualiserRecapitulatif();
})();
