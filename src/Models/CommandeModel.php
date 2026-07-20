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

    public static function findByIdPourUtilisateur(int $id, int $utilisateurId): ?array
    {
        $commande = self::findById($id);
        if ($commande === null || (int) $commande['utilisateur_id'] !== $utilisateurId) {
            return null;
        }
        return $commande;
    }

    public static function listByUtilisateur(int $utilisateurId): array
    {
        $stmt = getPDO()->prepare(
            'SELECT c.*, sc.code AS statut_code, sc.libelle AS statut_libelle, m.titre AS menu_titre
             FROM commande c
             JOIN statut_commande sc ON sc.statut_id = c.statut_id
             JOIN menu m ON m.menu_id = c.menu_id
             WHERE c.utilisateur_id = :utilisateur_id
             ORDER BY c.date_commande DESC'
        );
        $stmt->execute(['utilisateur_id' => $utilisateurId]);
        return $stmt->fetchAll();
    }

    public static function historique(int $commandeId): array
    {
        $stmt = getPDO()->prepare(
            'SELECT ch.*, sc.libelle AS statut_libelle
             FROM commande_historique ch
             JOIN statut_commande sc ON sc.statut_id = ch.statut_id
             WHERE ch.commande_id = :id
             ORDER BY ch.date_heure'
        );
        $stmt->execute(['id' => $commandeId]);
        return $stmt->fetchAll();
    }

    /** Modification par le client : uniquement autorisee tant que la commande est "en_attente". */
    public static function modifierParUtilisateur(int $commandeId, array $donnees): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE commande SET
                date_prestation = :date_prestation, heure_livraison = :heure_livraison,
                adresse_livraison = :adresse_livraison, ville_livraison = :ville_livraison,
                code_postal_livraison = :code_postal_livraison, distance_km = :distance_km,
                frais_livraison = :frais_livraison, nombre_personnes = :nombre_personnes,
                reduction_pourcentage = :reduction_pourcentage, prix_total = :prix_total
             WHERE commande_id = :id'
        );
        $stmt->execute([
            'date_prestation'       => $donnees['date_prestation'],
            'heure_livraison'       => $donnees['heure_livraison'],
            'adresse_livraison'     => $donnees['adresse_livraison'],
            'ville_livraison'       => $donnees['ville_livraison'],
            'code_postal_livraison' => $donnees['code_postal_livraison'],
            'distance_km'           => $donnees['distance_km'],
            'frais_livraison'       => $donnees['frais_livraison'],
            'nombre_personnes'      => $donnees['nombre_personnes'],
            'reduction_pourcentage' => $donnees['reduction_pourcentage'],
            'prix_total'            => $donnees['prix_total'],
            'id'                    => $commandeId,
        ]);
    }

    /** Annulation par le client (uniquement si "en_attente") : remet le stock du menu. */
    public static function annulerParUtilisateur(int $commandeId, int $menuId): void
    {
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $statutId = self::statutIdParCode('annulee');
            $pdo->prepare('UPDATE commande SET statut_id = :statut_id WHERE commande_id = :id')
                ->execute(['statut_id' => $statutId, 'id' => $commandeId]);
            $pdo->prepare('UPDATE menu SET stock_disponible = stock_disponible + 1 WHERE menu_id = :id')
                ->execute(['id' => $menuId]);
            $pdo->prepare('INSERT INTO commande_historique (commande_id, statut_id) VALUES (:id, :statut_id)')
                ->execute(['id' => $commandeId, 'statut_id' => $statutId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ---- Gestion (espace employe / administrateur) ----------------------

    private const ORDRE_WORKFLOW = [
        'en_attente'                 => 'accepte',
        'accepte'                    => 'en_preparation',
        'en_preparation'             => 'en_cours_livraison',
        'en_cours_livraison'         => 'livre',
        'en_attente_retour_materiel' => 'terminee',
    ];

    /** Determine le prochain statut logique, en tenant compte du pret de materiel. */
    public static function prochainStatutCode(array $commande): ?string
    {
        if ($commande['statut_code'] === 'livre') {
            return (bool) $commande['pret_materiel'] ? 'en_attente_retour_materiel' : 'terminee';
        }
        return self::ORDRE_WORKFLOW[$commande['statut_code']] ?? null;
    }

    /**
     * @param array{prix_min?:float,statut_code?:string,recherche?:string} $filtres
     */
    public static function listAll(array $filtres = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filtres['statut_code'])) {
            $conditions[] = 'sc.code = :statut_code';
            $params['statut_code'] = $filtres['statut_code'];
        }
        if (!empty($filtres['recherche'])) {
            $conditions[] = '(c.numero_commande LIKE :recherche OR u.nom LIKE :recherche OR u.prenom LIKE :recherche OR u.email LIKE :recherche)';
            $params['recherche'] = '%' . $filtres['recherche'] . '%';
        }

        $sql = 'SELECT c.*, sc.code AS statut_code, sc.libelle AS statut_libelle, m.titre AS menu_titre,
                       u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom, u.email AS utilisateur_email
                FROM commande c
                JOIN statut_commande sc ON sc.statut_id = c.statut_id
                JOIN menu m ON m.menu_id = c.menu_id
                JOIN utilisateur u ON u.utilisateur_id = c.utilisateur_id'
            . (!empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '')
            . ' ORDER BY c.date_commande DESC';

        $stmt = getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function setPretMateriel(int $commandeId, bool $pretMateriel): void
    {
        $stmt = getPDO()->prepare('UPDATE commande SET pret_materiel = :pret WHERE commande_id = :id');
        $stmt->execute(['pret' => $pretMateriel ? 1 : 0, 'id' => $commandeId]);
    }

    /** Fait avancer la commande au prochain statut du workflow. */
    public static function avancerStatut(int $commandeId): array
    {
        $commande = self::findById($commandeId);
        $prochainCode = self::prochainStatutCode($commande);
        if ($prochainCode === null) {
            return $commande;
        }

        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $statutId = self::statutIdParCode($prochainCode);
            $dateLimiteMateriel = $prochainCode === 'en_attente_retour_materiel'
                ? self::ajouterJoursOuvres(new DateTimeImmutable(), 10)->format('Y-m-d H:i:s')
                : null;

            $stmt = $pdo->prepare(
                'UPDATE commande SET statut_id = :statut_id, materiel_date_limite = :date_limite WHERE commande_id = :id'
            );
            $stmt->execute(['statut_id' => $statutId, 'date_limite' => $dateLimiteMateriel, 'id' => $commandeId]);

            $pdo->prepare('INSERT INTO commande_historique (commande_id, statut_id) VALUES (:id, :statut_id)')
                ->execute(['id' => $commandeId, 'statut_id' => $statutId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::findById($commandeId);
    }

    public static function confirmerRetourMateriel(int $commandeId): array
    {
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $statutId = self::statutIdParCode('terminee');
            $pdo->prepare('UPDATE commande SET statut_id = :statut_id, materiel_restitue = 1 WHERE commande_id = :id')
                ->execute(['statut_id' => $statutId, 'id' => $commandeId]);
            $pdo->prepare('INSERT INTO commande_historique (commande_id, statut_id) VALUES (:id, :statut_id)')
                ->execute(['id' => $commandeId, 'statut_id' => $statutId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findById($commandeId);
    }

    /** Annulation par un employe : necessite la preuve d'une prise de contact avec le client. */
    public static function annulerParEmploye(int $commandeId, int $menuId, string $motif, string $modeContact): array
    {
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $statutId = self::statutIdParCode('annulee');
            $pdo->prepare(
                'UPDATE commande SET statut_id = :statut_id, motif_annulation = :motif, mode_contact_annulation = :mode
                 WHERE commande_id = :id'
            )->execute(['statut_id' => $statutId, 'motif' => $motif, 'mode' => $modeContact, 'id' => $commandeId]);
            $pdo->prepare('UPDATE menu SET stock_disponible = stock_disponible + 1 WHERE menu_id = :id')
                ->execute(['id' => $menuId]);
            $pdo->prepare('INSERT INTO commande_historique (commande_id, statut_id, commentaire) VALUES (:id, :statut_id, :commentaire)')
                ->execute(['id' => $commandeId, 'statut_id' => $statutId, 'commentaire' => $motif]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findById($commandeId);
    }

    private static function ajouterJoursOuvres(DateTimeImmutable $date, int $jours): DateTimeImmutable
    {
        $restants = $jours;
        while ($restants > 0) {
            $date = $date->modify('+1 day');
            if ((int) $date->format('N') < 6) {
                $restants--;
            }
        }
        return $date;
    }
}
