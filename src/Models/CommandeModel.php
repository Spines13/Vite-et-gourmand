<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class CommandeModel
{
    public static function statutIdParCode(string $code): int
    {
        $stmt = getPDO()->prepare('SELECT statut_id FROM statut_commande WHERE code = :code');
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn();
    }

    public static function genererNumero(): string
    {
        return 'CMD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Cree la commande, decremente le stock du menu et trace le premier statut.
     * @return array La commande creee (avec son id)
     */
    public static function create(array $donnees): array
    {
        $pdo = getPDO();
        $pdo->beginTransaction();

        try {
            $statutId = self::statutIdParCode('en_attente');
            $numero = self::genererNumero();

            $stmt = $pdo->prepare(
                'INSERT INTO commande
                    (numero_commande, utilisateur_id, menu_id, statut_id, date_prestation, heure_livraison,
                     adresse_livraison, ville_livraison, code_postal_livraison, distance_km, frais_livraison,
                     nombre_personnes, prix_menu_unitaire, reduction_pourcentage, prix_total)
                 VALUES
                    (:numero, :utilisateur_id, :menu_id, :statut_id, :date_prestation, :heure_livraison,
                     :adresse_livraison, :ville_livraison, :code_postal_livraison, :distance_km, :frais_livraison,
                     :nombre_personnes, :prix_menu_unitaire, :reduction_pourcentage, :prix_total)'
            );
            $stmt->execute([
                'numero'                => $numero,
                'utilisateur_id'        => $donnees['utilisateur_id'],
                'menu_id'               => $donnees['menu_id'],
                'statut_id'             => $statutId,
                'date_prestation'       => $donnees['date_prestation'],
                'heure_livraison'       => $donnees['heure_livraison'],
                'adresse_livraison'     => $donnees['adresse_livraison'],
                'ville_livraison'       => $donnees['ville_livraison'],
                'code_postal_livraison' => $donnees['code_postal_livraison'],
                'distance_km'           => $donnees['distance_km'],
                'frais_livraison'       => $donnees['frais_livraison'],
                'nombre_personnes'      => $donnees['nombre_personnes'],
                'prix_menu_unitaire'    => $donnees['prix_menu_unitaire'],
                'reduction_pourcentage' => $donnees['reduction_pourcentage'],
                'prix_total'            => $donnees['prix_total'],
            ]);

            $commandeId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE menu SET stock_disponible = stock_disponible - 1 WHERE menu_id = :id')
                ->execute(['id' => $donnees['menu_id']]);

            $pdo->prepare(
                'INSERT INTO commande_historique (commande_id, statut_id) VALUES (:commande_id, :statut_id)'
            )->execute(['commande_id' => $commandeId, 'statut_id' => $statutId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::findById($commandeId);
    }

    public static function findById(int $id): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT c.*, sc.code AS statut_code, sc.libelle AS statut_libelle,
                    m.titre AS menu_titre, m.nombre_personne_minimum
             FROM commande c
             JOIN statut_commande sc ON sc.statut_id = c.statut_id
             JOIN menu m ON m.menu_id = c.menu_id
             WHERE c.commande_id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findByNumeroPourUtilisateur(string $numero, int $utilisateurId): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT c.*, sc.code AS statut_code, sc.libelle AS statut_libelle,
                    m.titre AS menu_titre, m.nombre_personne_minimum
             FROM commande c
             JOIN statut_commande sc ON sc.statut_id = c.statut_id
             JOIN menu m ON m.menu_id = c.menu_id
             WHERE c.numero_commande = :numero AND c.utilisateur_id = :utilisateur_id'
        );
        $stmt->execute(['numero' => $numero, 'utilisateur_id' => $utilisateurId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
