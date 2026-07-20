<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?: 1 ?>">
</head>
<body>
<a class="skip-link" href="#contenu-principal">Aller au contenu principal</a>
<?php require __DIR__ . '/nav.php'; ?>
<main id="contenu-principal">
<?php foreach (getFlashes() as $flash): ?>
    <p class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></p>
<?php endforeach; ?>
