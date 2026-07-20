<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Env.php';

/**
 * Lecture des statistiques de commandes depuis MongoDB (collection commande_analytics),
 * cf. database/mongodb/README.md. Utilisee par l'espace administrateur.
 */
final class AnalyticsService
{
    public static function estDisponible(): bool
    {
        return class_exists(\MongoDB\Client::class);
    }

    private static function collection(): ?\MongoDB\Collection
    {
        if (!self::estDisponible()) {
            return null;
        }
        try {
            loadEnv(__DIR__ . '/../../.env');
            $client = new \MongoDB\Client(env('MONGO_URI', 'mongodb://127.0.0.1:27017'));
            return $client->selectDatabase(env('MONGO_DB', 'vite_et_gourmand'))->selectCollection('commande_analytics');
        } catch (\Throwable $e) {
            error_log('AnalyticsService: connexion Mongo echouee - ' . $e->getMessage());
            return null;
        }
    }

    /** Nombre de commandes par menu, toutes commandes confondues (hors annulees). */
    public static function commandesParMenu(): array
    {
        $collection = self::collection();
        if ($collection === null) {
            return [];
        }
        try {
            $curseur = $collection->aggregate([
                ['$match' => ['statut' => ['$ne' => 'annulee']]],
                ['$group' => [
                    '_id' => '$menu_id',
                    'menu_titre' => ['$first' => '$menu_titre'],
                    'total_commandes' => ['$sum' => 1],
                ]],
                ['$sort' => ['total_commandes' => -1]],
            ]);
            return array_map(
                static fn ($doc) => ['menu_id' => $doc->_id, 'menu_titre' => $doc->menu_titre, 'total_commandes' => $doc->total_commandes],
                $curseur->toArray()
            );
        } catch (\Throwable $e) {
            error_log('AnalyticsService: agregation echouee - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Chiffre d'affaires par menu, filtrable par menu et par periode.
     * @return array<int, array{menu_id:int, menu_titre:string, chiffre_affaires:float, nombre_commandes:int}>
     */
    public static function chiffreAffairesParMenu(?int $menuId = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $collection = self::collection();
        if ($collection === null) {
            return [];
        }

        $match = ['statut' => ['$ne' => 'annulee']];
        if ($menuId !== null) {
            $match['menu_id'] = $menuId;
        }
        if ($dateDebut !== null || $dateFin !== null) {
            $match['date_commande'] = [];
            if ($dateDebut !== null) {
                $match['date_commande']['$gte'] = new \MongoDB\BSON\UTCDateTime(strtotime($dateDebut) * 1000);
            }
            if ($dateFin !== null) {
                $match['date_commande']['$lte'] = new \MongoDB\BSON\UTCDateTime((strtotime($dateFin) + 86399) * 1000);
            }
        }

        try {
            $curseur = $collection->aggregate([
                ['$match' => $match],
                ['$group' => [
                    '_id' => '$menu_id',
                    'menu_titre' => ['$first' => '$menu_titre'],
                    'chiffre_affaires' => ['$sum' => '$prix_total'],
                    'nombre_commandes' => ['$sum' => 1],
                ]],
                ['$sort' => ['chiffre_affaires' => -1]],
            ]);
            return array_map(
                static fn ($doc) => [
                    'menu_id'          => $doc->_id,
                    'menu_titre'       => $doc->menu_titre,
                    'chiffre_affaires' => (float) $doc->chiffre_affaires,
                    'nombre_commandes' => $doc->nombre_commandes,
                ],
                $curseur->toArray()
            );
        } catch (\Throwable $e) {
            error_log('AnalyticsService: agregation CA echouee - ' . $e->getMessage());
            return [];
        }
    }
}
