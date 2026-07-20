<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class HoraireModel
{
    private const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    public static function listAll(): array
    {
        $stmt = getPDO()->query(
            "SELECT * FROM horaire ORDER BY FIELD(jour, 'lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche')"
        );
        return $stmt->fetchAll();
    }

    /** @param array<string, array{ferme: bool, heure_ouverture: ?string, heure_fermeture: ?string}> $horairesParJour */
    public static function mettreAJour(array $horairesParJour): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE horaire SET heure_ouverture = :ouverture, heure_fermeture = :fermeture, ferme = :ferme WHERE jour = :jour'
        );
        foreach (self::JOURS as $jour) {
            $donnees = $horairesParJour[$jour] ?? ['ferme' => true, 'heure_ouverture' => null, 'heure_fermeture' => null];
            $stmt->execute([
                'ouverture' => $donnees['ferme'] ? null : $donnees['heure_ouverture'],
                'fermeture' => $donnees['ferme'] ? null : $donnees['heure_fermeture'],
                'ferme'     => $donnees['ferme'] ? 1 : 0,
                'jour'      => $jour,
            ]);
        }
    }
}
