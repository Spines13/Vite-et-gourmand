<?php
declare(strict_types=1);

require_once __DIR__ . '/Env.php';

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    loadEnv(__DIR__ . '/../../.env');

    $host    = env('DB_HOST', '127.0.0.1');
    $port    = env('DB_PORT', '3306');
    $name    = env('DB_NAME', 'vite_et_gourmand');
    $user    = env('DB_USER', 'root');
    $pass    = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
