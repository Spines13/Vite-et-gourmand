<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/UtilisateurModel.php';

final class AuthService
{
    public static function currentUser(): ?array
    {
        if (empty($_SESSION['utilisateur_id'])) {
            return null;
        }
        return UtilisateurModel::findById((int) $_SESSION['utilisateur_id']);
    }

    public static function attemptLogin(string $email, string $motDePasse): bool
    {
        $utilisateur = UtilisateurModel::findByEmail($email);
        if ($utilisateur === null || !password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            return false;
        }
        if (!(bool) $utilisateur['actif']) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['utilisateur_id'] = (int) $utilisateur['utilisateur_id'];
        $_SESSION['role']           = $utilisateur['role_code'];
        $_SESSION['prenom']         = $utilisateur['prenom'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): array
    {
        if (empty($_SESSION['utilisateur_id'])) {
            flash('error', 'Veuillez vous connecter pour accéder à cette page.');
            redirect('/connexion.php');
        }

        $user = self::currentUser();
        if ($user === null || !(bool) $user['actif']) {
            self::logout();
            flash('error', 'Votre session a expiré, merci de vous reconnecter.');
            redirect('/connexion.php');
        }

        return $user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role_code'], $roles, true)) {
            http_response_code(403);
            exit('Accès refusé.');
        }
        return $user;
    }

    public static function requireGuest(): void
    {
        if (!empty($_SESSION['utilisateur_id'])) {
            redirect('/');
        }
    }
}
