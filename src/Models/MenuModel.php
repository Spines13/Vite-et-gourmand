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

    public static function imagesDetaillees(int $menuId): array
    {
        $stmt = getPDO()->prepare('SELECT image_id, chemin_image FROM menu_image WHERE menu_id = :id ORDER BY ordre');
        $stmt->execute(['id' => $menuId]);
        return $stmt->fetchAll();
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

    // ---- Gestion (espace employe / administrateur) ----------------------

    public static function listPourGestion(): array
    {
        $stmt = getPDO()->query(
            'SELECT m.menu_id, m.titre, m.actif, m.stock_disponible, t.libelle AS theme
             FROM menu m JOIN theme t ON t.theme_id = m.theme_id
             ORDER BY m.actif DESC, m.titre'
        );
        return $stmt->fetchAll();
    }

    public static function findPourGestion(int $id): ?array
    {
        $stmt = getPDO()->prepare('SELECT m.*, t.libelle AS theme FROM menu m JOIN theme t ON t.theme_id = m.theme_id WHERE m.menu_id = :id');
        $stmt->execute(['id' => $id]);
        $menu = $stmt->fetch();
        if ($menu === false) {
            return null;
        }
        $menu['images'] = self::imagesDetaillees($id);
        $menu['regime_ids'] = array_column(self::regimesIds($id), 'regime_id');
        $menu['plat_ids'] = array_column(self::platsIds($id), 'plat_id');
        return $menu;
    }

    private static function regimesIds(int $menuId): array
    {
        $stmt = getPDO()->prepare('SELECT regime_id FROM menu_regime WHERE menu_id = :id');
        $stmt->execute(['id' => $menuId]);
        return $stmt->fetchAll();
    }

    private static function platsIds(int $menuId): array
    {
        $stmt = getPDO()->prepare('SELECT plat_id FROM menu_plat WHERE menu_id = :id');
        $stmt->execute(['id' => $menuId]);
        return $stmt->fetchAll();
    }

    /** @param int[] $regimeIds @param int[] $platIds */
    public static function creer(array $donnees, array $regimeIds, array $platIds): int
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO menu (titre, description, theme_id, nombre_personne_minimum, prix_personne_minimum, conditions, stock_disponible, actif)
             VALUES (:titre, :description, :theme_id, :nombre_personne_minimum, :prix_personne_minimum, :conditions, :stock_disponible, 1)'
        );
        $stmt->execute([
            'titre'                   => $donnees['titre'],
            'description'             => $donnees['description'],
            'theme_id'                => $donnees['theme_id'],
            'nombre_personne_minimum' => $donnees['nombre_personne_minimum'],
            'prix_personne_minimum'   => $donnees['prix_personne_minimum'],
            'conditions'              => $donnees['conditions'],
            'stock_disponible'        => $donnees['stock_disponible'],
        ]);
        $id = (int) $pdo->lastInsertId();
        self::associer($id, $regimeIds, $platIds);
        return $id;
    }

    /** @param int[] $regimeIds @param int[] $platIds */
    public static function modifier(int $id, array $donnees, array $regimeIds, array $platIds): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE menu SET titre = :titre, description = :description, theme_id = :theme_id,
                nombre_personne_minimum = :nombre_personne_minimum, prix_personne_minimum = :prix_personne_minimum,
                conditions = :conditions, stock_disponible = :stock_disponible
             WHERE menu_id = :id'
        );
        $stmt->execute([
            'titre'                   => $donnees['titre'],
            'description'             => $donnees['description'],
            'theme_id'                => $donnees['theme_id'],
            'nombre_personne_minimum' => $donnees['nombre_personne_minimum'],
            'prix_personne_minimum'   => $donnees['prix_personne_minimum'],
            'conditions'              => $donnees['conditions'],
            'stock_disponible'        => $donnees['stock_disponible'],
            'id'                      => $id,
        ]);
        self::associer($id, $regimeIds, $platIds);
    }

    /** @param int[] $regimeIds @param int[] $platIds */
    private static function associer(int $menuId, array $regimeIds, array $platIds): void
    {
        $pdo = getPDO();
        $pdo->prepare('DELETE FROM menu_regime WHERE menu_id = :id')->execute(['id' => $menuId]);
        $pdo->prepare('DELETE FROM menu_plat WHERE menu_id = :id')->execute(['id' => $menuId]);

        $insererRegime = $pdo->prepare('INSERT INTO menu_regime (menu_id, regime_id) VALUES (:menu_id, :regime_id)');
        foreach ($regimeIds as $regimeId) {
            $insererRegime->execute(['menu_id' => $menuId, 'regime_id' => $regimeId]);
        }

        $insererPlat = $pdo->prepare('INSERT INTO menu_plat (menu_id, plat_id) VALUES (:menu_id, :plat_id)');
        foreach ($platIds as $platId) {
            $insererPlat->execute(['menu_id' => $menuId, 'plat_id' => $platId]);
        }
    }

    public static function setActif(int $id, bool $actif): void
    {
        $stmt = getPDO()->prepare('UPDATE menu SET actif = :actif WHERE menu_id = :id');
        $stmt->execute(['actif' => $actif ? 1 : 0, 'id' => $id]);
    }

    public static function ajouterImage(int $menuId, string $chemin): void
    {
        $ordre = getPDO()->prepare('SELECT COALESCE(MAX(ordre), -1) + 1 FROM menu_image WHERE menu_id = :id');
        $ordre->execute(['id' => $menuId]);
        $stmt = getPDO()->prepare('INSERT INTO menu_image (menu_id, chemin_image, ordre) VALUES (:menu_id, :chemin, :ordre)');
        $stmt->execute(['menu_id' => $menuId, 'chemin' => $chemin, 'ordre' => (int) $ordre->fetchColumn()]);
    }

    public static function supprimerImage(int $imageId, int $menuId): void
    {
        $stmt = getPDO()->prepare('DELETE FROM menu_image WHERE image_id = :image_id AND menu_id = :menu_id');
        $stmt->execute(['image_id' => $imageId, 'menu_id' => $menuId]);
    }
}
