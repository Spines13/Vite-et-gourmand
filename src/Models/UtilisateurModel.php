<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Database.php';

final class UtilisateurModel
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT u.*, r.code AS role_code
             FROM utilisateur u
             JOIN role r ON r.role_id = u.role_id
             WHERE u.email = :email'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT u.*, r.code AS role_code
             FROM utilisateur u
             JOIN role r ON r.role_id = u.role_id
             WHERE u.utilisateur_id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(array $data): int
    {
        $pdo = getPDO();
        $roleId = $pdo->prepare('SELECT role_id FROM role WHERE code = :code');
        $roleId->execute(['code' => 'utilisateur']);
        $roleIdValue = $roleId->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO utilisateur
                (email, mot_de_passe, nom, prenom, telephone, adresse_postale, ville, code_postal, pays, role_id)
             VALUES
                (:email, :mot_de_passe, :nom, :prenom, :telephone, :adresse_postale, :ville, :code_postal, :pays, :role_id)'
        );
        $stmt->execute([
            'email'            => $data['email'],
            'mot_de_passe'     => password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
            'nom'              => $data['nom'],
            'prenom'           => $data['prenom'],
            'telephone'        => $data['telephone'],
            'adresse_postale'  => $data['adresse_postale'],
            'ville'            => $data['ville'],
            'code_postal'      => $data['code_postal'],
            'pays'             => $data['pays'] ?? 'France',
            'role_id'          => $roleIdValue,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function createEmploye(string $email, string $motDePasseClair, string $nom, string $prenom): int
    {
        $pdo = getPDO();
        $roleId = $pdo->prepare('SELECT role_id FROM role WHERE code = :code');
        $roleId->execute(['code' => 'employe']);
        $roleIdValue = $roleId->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, role_id, actif)
             VALUES (:email, :mot_de_passe, :nom, :prenom, :role_id, 1)'
        );
        $stmt->execute([
            'email'        => $email,
            'mot_de_passe' => password_hash($motDePasseClair, PASSWORD_DEFAULT),
            'nom'          => $nom,
            'prenom'       => $prenom,
            'role_id'      => $roleIdValue,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function setActif(int $id, bool $actif): void
    {
        $stmt = getPDO()->prepare('UPDATE utilisateur SET actif = :actif WHERE utilisateur_id = :id');
        $stmt->execute(['actif' => $actif ? 1 : 0, 'id' => $id]);
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE utilisateur SET
                nom = :nom, prenom = :prenom, telephone = :telephone,
                adresse_postale = :adresse_postale, ville = :ville,
                code_postal = :code_postal, pays = :pays
             WHERE utilisateur_id = :id'
        );
        $stmt->execute([
            'nom'             => $data['nom'],
            'prenom'          => $data['prenom'],
            'telephone'       => $data['telephone'],
            'adresse_postale' => $data['adresse_postale'],
            'ville'           => $data['ville'],
            'code_postal'     => $data['code_postal'],
            'pays'            => $data['pays'] ?? 'France',
            'id'              => $id,
        ]);
    }

    public static function updatePassword(int $id, string $motDePasseClair): void
    {
        $stmt = getPDO()->prepare('UPDATE utilisateur SET mot_de_passe = :hash WHERE utilisateur_id = :id');
        $stmt->execute(['hash' => password_hash($motDePasseClair, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public static function setResetToken(int $id, string $tokenHash, string $expireAt): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE utilisateur SET token_reset = :token, token_reset_expire = :expire WHERE utilisateur_id = :id'
        );
        $stmt->execute(['token' => $tokenHash, 'expire' => $expireAt, 'id' => $id]);
    }

    public static function findByValidResetTokenHash(string $tokenHash): ?array
    {
        $stmt = getPDO()->prepare(
            'SELECT * FROM utilisateur WHERE token_reset = :token AND token_reset_expire > NOW()'
        );
        $stmt->execute(['token' => $tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function clearResetToken(int $id): void
    {
        $stmt = getPDO()->prepare(
            'UPDATE utilisateur SET token_reset = NULL, token_reset_expire = NULL WHERE utilisateur_id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function listEmployes(): array
    {
        $stmt = getPDO()->prepare(
            "SELECT u.* FROM utilisateur u
             JOIN role r ON r.role_id = u.role_id
             WHERE r.code = 'employe'
             ORDER BY u.nom, u.prenom"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
