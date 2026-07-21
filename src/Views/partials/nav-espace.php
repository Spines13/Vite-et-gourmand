<?php
declare(strict_types=1);

if (($_SESSION['role'] ?? null) === 'administrateur') {
    require __DIR__ . '/nav-admin.php';
} else {
    require __DIR__ . '/nav-employe.php';
}
