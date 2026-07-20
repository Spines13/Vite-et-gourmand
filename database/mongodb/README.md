# Base NoSQL - Vite & Gourmand

## Pourquoi une base non relationnelle ici ?

Le cahier des charges impose que l'espace **Administrateur** affiche :

- le nombre de commandes par menu, comparables entre elles via un graphique,
  **avec des données venant explicitement d'une base non relationnelle** ;
- un chiffre d'affaires par menu, filtrable par menu et par période.

MySQL reste la source de vérité transactionnelle (intégrité référentielle,
contraintes, workflow des statuts). MongoDB est utilisé en complément comme
**magasin de reporting dénormalisé** : chaque commande y est répliquée sous
forme d'un document plat, ce qui rend les agrégations (regroupement par menu,
sommes, filtres de date) rapides et simples à écrire (pipeline
d'agrégation), sans jointures ni recalcul coûteux côté SQL. C'est un usage
classique de persistance polyglotte : le relationnel pour l'OLTP, le
document pour l'analytique/reporting.

## Stratégie de synchronisation

Pas de CDC/triggers pour un projet de cette taille : la couche applicative
PHP fait un **double-écriture** simple. À chaque création ou changement de
statut d'une commande côté MySQL, l'application fait un `upsert` du document
correspondant dans MongoDB, avec `commande_id` (l'ID MySQL) comme clé
d'identification stable. En cas d'échec d'écriture Mongo, la commande reste
valide côté MySQL (source de vérité) ; un job de resynchronisation pourra
être ajouté si besoin.

## Collection `commande_analytics`

Un document par commande.

```json
{
  "_id": "cmd_1",
  "commande_id": 1,
  "numero_commande": "CMD-2026-0001",
  "menu_id": 1,
  "menu_titre": "Menu de Noël Traditionnel",
  "theme": "Noel",
  "utilisateur_id": 3,
  "ville_livraison": "Bordeaux",
  "date_commande": { "$date": "2026-06-01T10:15:00Z" },
  "date_prestation": { "$date": "2026-06-20" },
  "nombre_personnes": 11,
  "prix_menu": 330.00,
  "frais_livraison": 0.00,
  "reduction_pourcentage": 10.00,
  "prix_total": 297.00,
  "statut": "terminee"
}
```

`_id` utilise un préfixe lisible (`cmd_<commande_id>`) plutôt qu'un
`ObjectId` généré, pour permettre un `upsert` direct par `commande_id` sans
requête de recherche préalable.

### Index

```js
db.commande_analytics.createIndex({ menu_id: 1 });
db.commande_analytics.createIndex({ statut: 1 });
db.commande_analytics.createIndex({ date_commande: 1 });
```

## Exemples de pipelines d'agrégation

**Nombre de commandes par menu (graphique comparatif) :**

```js
db.commande_analytics.aggregate([
  { $group: {
      _id: "$menu_id",
      menu_titre: { $first: "$menu_titre" },
      total_commandes: { $sum: 1 }
  }},
  { $sort: { total_commandes: -1 } }
]);
```

**Chiffre d'affaires par menu, filtré sur une période (commandes non
annulées uniquement) :**

```js
db.commande_analytics.aggregate([
  { $match: {
      statut: { $ne: "annulee" },
      date_commande: { $gte: ISODate("2026-01-01"), $lte: ISODate("2026-12-31") }
  }},
  { $group: {
      _id: "$menu_id",
      menu_titre: { $first: "$menu_titre" },
      chiffre_affaires: { $sum: "$prix_total" },
      nombre_commandes: { $sum: 1 }
  }},
  { $sort: { chiffre_affaires: -1 } }
]);
```

**Chiffre d'affaires d'un menu précis :**

```js
db.commande_analytics.aggregate([
  { $match: { menu_id: 1, statut: { $ne: "annulee" } } },
  { $group: { _id: "$menu_id", chiffre_affaires: { $sum: "$prix_total" } } }
]);
```

## Installation locale (à faire quand on attaquera le back-end)

1. Installer MongoDB Community Server (https://www.mongodb.com/try/download/community).
2. Démarrer le service (`mongod`), écoute par défaut sur `localhost:27017`.
3. Charger la structure + les données de démo :
   ```
   mongosh < database/mongodb/init.js
   ```
