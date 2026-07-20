<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';

$filtres = [];
foreach (['prix_min', 'prix_max'] as $cle) {
    if (isset($_GET[$cle]) && is_numeric($_GET[$cle])) {
        $filtres[$cle] = (float) $_GET[$cle];
    }
}
foreach (['theme_id', 'regime_id', 'personnes_min'] as $cle) {
    if (isset($_GET[$cle]) && ctype_digit((string) $_GET[$cle])) {
        $filtres[$cle] = (int) $_GET[$cle];
    }
}

$menus = MenuModel::listActive($filtres);

jsonResponse([
    'menus' => array_map(static fn (array $m) => [
        'id'                       => (int) $m['menu_id'],
        'titre'                    => $m['titre'],
        'description'              => $m['description'],
        'theme'                    => $m['theme'],
        'nombre_personne_minimum'  => (int) $m['nombre_personne_minimum'],
        'prix_personne_minimum'    => (float) $m['prix_personne_minimum'],
        'disponible'               => (int) $m['stock_disponible'] > 0,
        'image'                    => $m['image'],
    ], $menus),
]);
