<?php
declare(strict_types=1);

// Equivalent PHP de init.js, pour les environnements sans mongosh installe.
// Usage : php database/mongodb/init.php

require_once __DIR__ . '/../../vendor/autoload.php';

$client = new MongoDB\Client('mongodb://127.0.0.1:27017');
$db = $client->selectDatabase('vite_et_gourmand');

try {
    $db->createCollection('commande_analytics', [
        'validator' => [
            '$jsonSchema' => [
                'bsonType' => 'object',
                'required' => [
                    'commande_id', 'numero_commande', 'menu_id', 'menu_titre',
                    'date_commande', 'nombre_personnes', 'prix_total', 'statut',
                ],
                'properties' => [
                    'commande_id'          => ['bsonType' => 'int'],
                    'numero_commande'      => ['bsonType' => 'string'],
                    'menu_id'              => ['bsonType' => 'int'],
                    'menu_titre'           => ['bsonType' => 'string'],
                    'theme'                => ['bsonType' => 'string'],
                    'utilisateur_id'       => ['bsonType' => 'int'],
                    'ville_livraison'      => ['bsonType' => 'string'],
                    'date_commande'        => ['bsonType' => 'date'],
                    'date_prestation'      => ['bsonType' => 'date'],
                    'nombre_personnes'     => ['bsonType' => 'int'],
                    'prix_menu'            => ['bsonType' => 'double'],
                    'frais_livraison'      => ['bsonType' => 'double'],
                    'reduction_pourcentage' => ['bsonType' => 'double'],
                    'prix_total'           => ['bsonType' => 'double'],
                    'statut'               => [
                        'enum' => [
                            'en_attente', 'accepte', 'en_preparation', 'en_cours_livraison',
                            'livre', 'en_attente_retour_materiel', 'terminee', 'annulee',
                        ],
                    ],
                ],
            ],
        ],
        'validationLevel' => 'moderate',
    ]);
    echo "Collection commande_analytics creee.\n";
} catch (MongoDB\Driver\Exception\CommandException $e) {
    echo "Collection deja existante (" . $e->getMessage() . ").\n";
}

$collection = $db->selectCollection('commande_analytics');
$collection->createIndex(['menu_id' => 1]);
$collection->createIndex(['statut' => 1]);
$collection->createIndex(['date_commande' => 1]);

$collection->deleteMany([]);
$collection->insertMany([
    [
        '_id' => 'cmd_1',
        'commande_id' => 1,
        'numero_commande' => 'CMD-2026-0001',
        'menu_id' => 1,
        'menu_titre' => 'Menu de Noël Traditionnel',
        'theme' => 'Noel',
        'utilisateur_id' => 3,
        'ville_livraison' => 'Bordeaux',
        'date_commande' => new MongoDB\BSON\UTCDateTime(strtotime('2026-06-01T10:15:00Z') * 1000),
        'date_prestation' => new MongoDB\BSON\UTCDateTime(strtotime('2026-06-20T00:00:00Z') * 1000),
        'nombre_personnes' => 11,
        'prix_menu' => 330.00,
        'frais_livraison' => 0.00,
        'reduction_pourcentage' => 10.00,
        'prix_total' => 297.00,
        'statut' => 'terminee',
    ],
    [
        '_id' => 'cmd_2',
        'commande_id' => 2,
        'numero_commande' => 'CMD-2026-0002',
        'menu_id' => 2,
        'menu_titre' => 'Menu de Pâques Végétarien',
        'theme' => 'Paques',
        'utilisateur_id' => 3,
        'ville_livraison' => 'Mérignac',
        'date_commande' => new MongoDB\BSON\UTCDateTime(strtotime('2026-07-15T09:30:00Z') * 1000),
        'date_prestation' => new MongoDB\BSON\UTCDateTime(strtotime('2026-07-25T00:00:00Z') * 1000),
        'nombre_personnes' => 4,
        'prix_menu' => 110.00,
        'frais_livraison' => 7.95,
        'reduction_pourcentage' => 0.00,
        'prix_total' => 117.95,
        'statut' => 'en_preparation',
    ],
]);

echo 'Collection commande_analytics initialisee avec ' . $collection->countDocuments() . " documents.\n";
