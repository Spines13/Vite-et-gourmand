<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class PlatModel
{
    public static function listAll(): array
    {
        return getPDO()->query(
            'SELECT plat_id, titre_plat, categorie, photo FROM plat ORDER BY FIELD(categorie, "entree","plat","dessert"), titre_plat'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getPDO()->prepare('SELECT * FROM plat WHERE plat_id = :id');
        $stmt->execute(['id' => $id]);
        $plat = $stmt->fetch();
        if ($plat === false) {
            return null;
        }
        $allergenes = getPDO()->prepare('SELECT allergene_id FROM plat_allergene WHERE plat_id = :id');
        $allergenes->execute(['id' => $id]);
        $plat['allergene_ids'] = array_column($allergenes->fetchAll(), 'allergene_id');
        return $plat;
    }

    public static function listAllergenes(): array
    {
        return getPDO()->query('SELECT allergene_id, libelle FROM allergene ORDER BY libelle')->fetchAll();
    }

    /** @param int[] $allergeneIds */
    public static function creer(string $titre, string $categorie, ?string $photo, array $allergeneIds): int
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO plat (titre_plat, categorie, photo) VALUES (:titre, :categorie, :photo)');
        $stmt->execute(['titre' => $titre, 'categorie' => $categorie, 'photo' => $photo]);
        $id = (int) $pdo->lastInsertId();
        self::associerAllergenes($id, $allergeneIds);
        return $id;
    }

    /** @param int[] $allergeneIds */
    public static function modifier(int $id, string $titre, string $categorie, ?string $photo, array $allergeneIds): void
    {
        $pdo = getPDO();
        if ($photo !== null) {
            $stmt = $pdo->prepare('UPDATE plat SET titre_plat = :titre, categorie = :categorie, photo = :photo WHERE plat_id = :id');
            $stmt->execute(['titre' => $titre, 'categorie' => $categorie, 'photo' => $photo, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE plat SET titre_plat = :titre, categorie = :categorie WHERE plat_id = :id');
            $stmt->execute(['titre' => $titre, 'categorie' => $categorie, 'id' => $id]);
        }
        self::associerAllergenes($id, $allergeneIds);
    }

    /** @param int[] $allergeneIds */
    private static function associerAllergenes(int $platId, array $allergeneIds): void
    {
        $pdo = getPDO();
        $pdo->prepare('DELETE FROM plat_allergene WHERE plat_id = :id')->execute(['id' => $platId]);
        $inserer = $pdo->prepare('INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (:plat_id, :allergene_id)');
        foreach ($allergeneIds as $allergeneId) {
            $inserer->execute(['plat_id' => $platId, 'allergene_id' => $allergeneId]);
        }
    }

    public static function supprimer(int $id): void
    {
        getPDO()->prepare('DELETE FROM plat WHERE plat_id = :id')->execute(['id' => $id]);
    }
}
