<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Env.php';

/**
 * Miroir des commandes dans MongoDB pour le reporting de l'espace administrateur
 * (cf. database/mongodb/README.md). Best-effort : si l'extension/serveur Mongo
 * n'est pas disponible, la synchronisation est simplement ignorée sans jamais
 * faire échouer la commande côté MySQL, qui reste la source de vérité.
 */
final class CommandeAnalyticsService
{
    public static function synchroniser(array $commande): void
    {
        if (!class_exists(\MongoDB\Client::class)) {
            return;
        }

        try {
            loadEnv(__DIR__ . '/../../.env');
            $client = new \MongoDB\Client(env('MONGO_URI', 'mongodb://127.0.0.1:27017'));
            $collection = $client
                ->selectDatabase(env('MONGO_DB', 'vite_et_gourmand'))
                ->selectCollection('commande_analytics');

            $collection->replaceOne(
                ['_id' => 'cmd_' . $commande['commande_id']],
                [
                    '_id'                   => 'cmd_' . $commande['commande_id'],
                    'commande_id'           => (int) $commande['commande_id'],
                    'numero_commande'       => $commande['numero_commande'],
                    'menu_id'               => (int) $commande['menu_id'],
                    'menu_titre'            => $commande['menu_titre'],
                    'utilisateur_id'        => (int) $commande['utilisateur_id'],
                    'ville_livraison'       => $commande['ville_livraison'],
                    'date_commande'         => new \MongoDB\BSON\UTCDateTime(strtotime($commande['date_commande']) * 1000),
                    'date_prestation'       => new \MongoDB\BSON\UTCDateTime(strtotime($commande['date_prestation']) * 1000),
                    'nombre_personnes'      => (int) $commande['nombre_personnes'],
                    'prix_menu'             => (float) $commande['prix_menu_unitaire'] * (int) $commande['nombre_personnes'],
                    'frais_livraison'       => (float) $commande['frais_livraison'],
                    'reduction_pourcentage' => (float) $commande['reduction_pourcentage'],
                    'prix_total'            => (float) $commande['prix_total'],
                    'statut'                => $commande['statut_code'],
                ],
                ['upsert' => true]
            );
        } catch (\Throwable $e) {
            error_log('CommandeAnalyticsService: synchronisation Mongo echouee - ' . $e->getMessage());
        }
    }
}
