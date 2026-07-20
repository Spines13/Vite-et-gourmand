<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class MenuModel
{
    /**
     * @param array{prix_min?:float,prix_max?:float,theme_id?:int,regime_id?:int,personnes_min?:int} $filtres
     */
    public static function listActive(array $filtres = []): array
    {
        $conditions = ['m.actif = 1'];
        $params = [];

        if (!empty($filtres['prix_min'])) {
            $conditions[] = 'm.prix_personne_minimum >= :prix_min';
            $params['prix_min'] = $filtres['prix_min'];
        }
        if (!empty($filtres['prix_max'])) {
            $conditions[] = 'm.prix_personne_minimum <= :prix_max';
            $params['prix_max'] = $filtres['prix_max'];
        }
        if (!empty($filtres['theme_id'])) {
            $conditions[] = 'm.theme_id = :theme_id';
            $params['theme_id'] = $filtres['theme_id'];
        }
        if (!empty($filtres['personnes_min'])) {
            $conditions[] = 'm.nombre_personne_minimum <= :personnes_min';
            $params['personnes_min'] = $filtres['personnes_min'];
        }

        $jointureRegime = '';
        if (!empty($filtres['regime_id'])) {
            $jointureRegime = 'JOIN menu_regime mr ON mr.menu_id = m.menu_id AND mr.regime_id = :regime_id';
            $params['regime_id'] = $filtres['regime_id'];
        }

        $sql = "SELECT m.menu_id, m.titre, m.description, m.nombre_personne_minimum,
                       m.prix_personne_minimum, m.stock_disponible, t.libelle AS theme,
                       (SELECT chemin_image FROM menu_image mi WHERE mi.menu_id = m.menu_id ORDER BY mi.ordre LIMIT 1) AS image
                FROM menu m
                JOIN theme t ON t.theme_id = m.theme_id
                {$jointureRegime}
                WHERE " . implode(' AND ', $conditions) . '
                ORDER BY m.titre';

        $stmt = getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT m.*, t.libelle AS theme
             FROM menu m
             JOIN theme t ON t.theme_id = m.theme_id
             WHERE m.menu_id = :id AND m.actif = 1'
        );
        $stmt->execute(['id' => $id]);
        $menu = $stmt->fetch();
        if ($menu === false) {
            return null;
        }

        $menu['images'] = self::images($id);
        $menu['regimes'] = self::regimes($id);
        $menu['plats'] = self::platsParCategorie($id);

        return $menu;
    }

    public static function images(int $menuId): array
    {
        $stmt = getPDO()->prepare('SELECT chemin_image FROM menu_image WHERE menu_id = :id ORDER BY ordre');
        $stmt->execute(['id' => $menuId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function regimes(int $menuId): array
    {
        $stmt = getPDO()->prepare(
            'SELECT r.libelle FROM regime r
             JOIN menu_regime mr ON mr.regime_id = r.regime_id
             WHERE mr.menu_id = :id
             ORDER BY r.libelle'
        );
        $stmt->execute(['id' => $menuId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array<string, array> plats groupes par categorie (entree, plat, dessert) avec leurs allergenes */
    public static function platsParCategorie(int $menuId): array
    {
        $stmt = getPDO()->prepare(
            'SELECT p.plat_id, p.titre_plat, p.categorie,
                    GROUP_CONCAT(a.libelle ORDER BY a.libelle SEPARATOR ", ") AS allergenes
             FROM plat p
             JOIN menu_plat mp ON mp.plat_id = p.plat_id
             LEFT JOIN plat_allergene pa ON pa.plat_id = p.plat_id
             LEFT JOIN allergene a ON a.allergene_id = pa.allergene_id
             WHERE mp.menu_id = :id
             GROUP BY p.plat_id, p.titre_plat, p.categorie
             ORDER BY FIELD(p.categorie, "entree", "plat", "dessert"), p.titre_plat'
        );
        $stmt->execute(['id' => $menuId]);

        $groupes = ['entree' => [], 'plat' => [], 'dessert' => []];
        foreach ($stmt->fetchAll() as $plat) {
            $groupes[$plat['categorie']][] = $plat;
        }
        return $groupes;
    }

    public static function listThemes(): array
    {
        return getPDO()->query('SELECT theme_id, libelle FROM theme ORDER BY libelle')->fetchAll();
    }

    public static function listRegimes(): array
    {
        return getPDO()->query('SELECT regime_id, libelle FROM regime ORDER BY libelle')->fetchAll();
    }
}
