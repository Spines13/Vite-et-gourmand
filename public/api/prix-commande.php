<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Config/bootstrap.php';
require_once __DIR__ . '/../../src/Models/MenuModel.php';
require_once __DIR__ . '/../../src/Services/PricingService.php';

$menuId = isset($_GET['menu_id']) && ctype_digit((string) $_GET['menu_id']) ? (int) $_GET['menu_id'] : 0;
$menu = $menuId > 0 ? MenuModel::find($menuId) : null;

if ($menu === null) {
    jsonResponse(['erreur' => 'Menu introuvable'], 404);
}

$nombrePersonnes = max(1, (int) ($_GET['nombre_personnes'] ?? $menu['nombre_personne_minimum']));
$ville = (string) ($_GET['ville_livraison'] ?? '');
$distance = (float) ($_GET['distance_km'] ?? 0);

if ($nombrePersonnes < (int) $menu['nombre_personne_minimum']) {
    jsonResponse([
        'erreur' => 'Le nombre de personnes doit être au moins de ' . $menu['nombre_personne_minimum'],
    ], 422);
}

$prix = PricingService::calculer($menu, $nombrePersonnes, $ville, $distance);

jsonResponse($prix);
