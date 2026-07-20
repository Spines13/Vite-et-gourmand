<?php
declare(strict_types=1);

final class PricingService
{
    private const FRAIS_LIVRAISON_BASE = 5.0;
    private const FRAIS_LIVRAISON_PAR_KM = 0.59;
    private const SEUIL_PERSONNES_SUPPLEMENTAIRES_REDUCTION = 5;
    private const TAUX_REDUCTION = 10.0;
    private const VILLE_SANS_FRAIS = 'bordeaux';

    /**
     * @param array{prix_personne_minimum: float, nombre_personne_minimum: int} $menu
     */
    public static function calculer(array $menu, int $nombrePersonnes, string $villeLivraison, float $distanceKm): array
    {
        $prixUnitaire = (float) $menu['prix_personne_minimum'] / max(1, (int) $menu['nombre_personne_minimum']);
        $prixMenu = round($prixUnitaire * $nombrePersonnes, 2);

        $reduction = $nombrePersonnes >= (int) $menu['nombre_personne_minimum'] + self::SEUIL_PERSONNES_SUPPLEMENTAIRES_REDUCTION
            ? self::TAUX_REDUCTION
            : 0.0;

        $livraisonGratuite = self::normaliserVille($villeLivraison) === self::VILLE_SANS_FRAIS;
        $fraisLivraison = $livraisonGratuite
            ? 0.0
            : round(self::FRAIS_LIVRAISON_BASE + self::FRAIS_LIVRAISON_PAR_KM * max(0, $distanceKm), 2);

        $prixMenuApresReduction = round($prixMenu * (1 - $reduction / 100), 2);
        $prixTotal = round($prixMenuApresReduction + $fraisLivraison, 2);

        return [
            'prix_unitaire'         => round($prixUnitaire, 2),
            'prix_menu'             => $prixMenu,
            'reduction_pourcentage' => $reduction,
            'frais_livraison'       => $fraisLivraison,
            'prix_total'            => $prixTotal,
        ];
    }

    private static function normaliserVille(string $ville): string
    {
        $ville = trim(mb_strtolower($ville));
        $ville = preg_replace('/[^a-z]/u', '', $ville) ?? $ville;
        return $ville;
    }
}
