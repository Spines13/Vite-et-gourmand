<?php
declare(strict_types=1);

// Resynchronise toutes les commandes MySQL vers MongoDB (commande_analytics).
// Utile si des commandes ont ete passees pendant que MongoDB etait arrete :
// la synchronisation normale (CommandeAnalyticsService) est "best-effort" et
// n'echoue jamais la commande, mais ne rattrape pas les commandes manquees.
// Usage : php database/mongodb/resync.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Config/Env.php';
require_once __DIR__ . '/../../src/Services/CommandeAnalyticsService.php';
require_once __DIR__ . '/../../src/Models/CommandeModel.php';

loadEnv(__DIR__ . '/../../.env');

$commandes = getPDO()->query(
    'SELECT c.*, sc.code AS statut_code, m.titre AS menu_titre
     FROM commande c
     JOIN statut_commande sc ON sc.statut_id = c.statut_id
     JOIN menu m ON m.menu_id = c.menu_id'
)->fetchAll();

foreach ($commandes as $commande) {
    CommandeAnalyticsService::synchroniser($commande);
    echo $commande['numero_commande'] . ' synchronisee.' . PHP_EOL;
}

echo count($commandes) . ' commande(s) resynchronisee(s) au total.' . PHP_EOL;
