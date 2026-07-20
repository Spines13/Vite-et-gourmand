<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class ContactModel
{
    public static function create(string $titre, string $description, string $email): void
    {
        $stmt = getPDO()->prepare(
            'INSERT INTO contact (titre, description, email) VALUES (:titre, :description, :email)'
        );
        $stmt->execute(['titre' => $titre, 'description' => $description, 'email' => $email]);
    }
}
