<?php
declare(strict_types=1);

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../Helpers/functions.php';

loadEnv(__DIR__ . '/../../.env');

date_default_timezone_set('Europe/Paris');

$appEnv = env('APP_ENV', 'local');
ini_set('display_errors', $appEnv === 'local' ? '1' : '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Jeton anti-CSRF partagé par tous les formulaires de l'application.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
