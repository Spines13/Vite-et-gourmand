<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class AvisModel
{
    public static function findByCommande(int $commandeId): ?array
    {
        $stmt = getPDO()->prepare('SELECT * FROM avis WHERE commande_id = :id');
        $stmt->execute(['id' => $commandeId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(int $commandeId, int $utilisateurId, int $note, string $commentaire): void
    {
        $stmt = getPDO()->prepare(
            'INSERT INTO avis (commande_id, utilisateur_id, note, commentaire, statut)
             VALUES (:commande_id, :utilisateur_id, :note, :commentaire, "en_attente")'
        );
        $stmt->execute([
            'commande_id'    => $commandeId,
            'utilisateur_id' => $utilisateurId,
            'note'           => $note,
            'commentaire'    => $commentaire,
        ]);
    }

    public static function listEnAttente(): array
    {
        $stmt = getPDO()->prepare(
            'SELECT a.*, u.prenom, u.nom, c.numero_commande
             FROM avis a
             JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id
             JOIN commande c ON c.commande_id = a.commande_id
             WHERE a.statut = "en_attente"
             ORDER BY a.date_creation'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function moderer(int $avisId, bool $valide): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE avis SET statut = :statut, date_moderation = NOW() WHERE avis_id = :id'
        );
        $stmt->execute(['statut' => $valide ? 'valide' : 'refuse', 'id' => $avisId]);
    }
}
