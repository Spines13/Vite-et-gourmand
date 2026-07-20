<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Env.php';

final class MailService
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $fromEmail = env('MAIL_FROM', 'contact@vite-et-gourmand.fr');
        $fromName  = env('MAIL_FROM_NAME', 'Vite & Gourmand');

        $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            "From: {$encodedFromName} <{$fromEmail}>",
        ]);

        $envoye = @mail($to, $encodedSubject, $htmlBody, $headers);
        if (!$envoye) {
            error_log("MailService: échec d'envoi vers {$to} (sujet: {$subject})");
        }
        return $envoye;
    }

    private static function layout(string $titre, string $corpsHtml): string
    {
        return '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;">'
            . '<h1 style="color:#b5482a;">Vite &amp; Gourmand</h1>'
            . '<h2>' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</h2>'
            . $corpsHtml
            . '<p style="margin-top:2rem;font-size:0.85rem;color:#666;">Ceci est un message automatique, merci de ne pas y répondre directement.</p>'
            . '</div>';
    }

    public static function envoyerBienvenue(string $to, string $prenom): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Votre compte Vite &amp; Gourmand a bien été créé. Vous pouvez dès à présent consulter '
            . 'nos menus et passer commande.</p>';
        return self::send($to, 'Bienvenue chez Vite & Gourmand', self::layout('Bienvenue !', $corps));
    }

    public static function envoyerReinitialisationMotDePasse(string $to, string $prenom, string $lien): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Vous avez demandé la réinitialisation de votre mot de passe. Ce lien est valable 1 heure :</p>'
            . '<p><a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '">Réinitialiser mon mot de passe</a></p>'
            . '<p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement ce message.</p>';
        return self::send($to, 'Réinitialisation de votre mot de passe', self::layout('Mot de passe oublié', $corps));
    }

    public static function envoyerConfirmationCommande(string $to, string $prenom, array $commande): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Votre commande <strong>' . htmlspecialchars($commande['numero_commande'], ENT_QUOTES, 'UTF-8') . '</strong> '
            . 'a bien été enregistrée pour le ' . htmlspecialchars($commande['date_prestation'], ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Montant total : ' . number_format((float) $commande['prix_total'], 2, ',', ' ') . ' €</p>'
            . '<p>Vous pouvez suivre son évolution depuis votre espace personnel.</p>';
        return self::send($to, 'Confirmation de votre commande ' . $commande['numero_commande'], self::layout('Commande confirmée', $corps));
    }

    public static function envoyerChangementStatut(string $to, string $prenom, array $commande, string $libelleStatut): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Votre commande <strong>' . htmlspecialchars($commande['numero_commande'], ENT_QUOTES, 'UTF-8') . '</strong> '
            . 'est maintenant au statut : <strong>' . htmlspecialchars($libelleStatut, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
        return self::send($to, 'Mise à jour de votre commande ' . $commande['numero_commande'], self::layout('Suivi de commande', $corps));
    }

    public static function envoyerRetourMaterielRequis(string $to, string $prenom, array $commande): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Du matériel vous a été prêté pour votre commande <strong>'
            . htmlspecialchars($commande['numero_commande'], ENT_QUOTES, 'UTF-8') . '</strong>. Merci de nous contacter '
            . 'afin d\'organiser sa restitution.</p>'
            . '<p><strong>Passé un délai de 10 jours ouvrés</strong> sans restitution, des frais de 600 € '
            . 'vous seront facturés, conformément à nos conditions générales de vente.</p>';
        return self::send($to, 'Restitution de matériel requise - ' . $commande['numero_commande'], self::layout('Retour de matériel', $corps));
    }

    public static function envoyerInvitationAvis(string $to, string $prenom, array $commande): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Votre commande <strong>' . htmlspecialchars($commande['numero_commande'], ENT_QUOTES, 'UTF-8') . '</strong> '
            . 'est terminée. Connectez-vous à votre espace pour nous laisser votre avis, cela nous aide énormément !</p>';
        return self::send($to, 'Donnez votre avis sur votre commande', self::layout('Votre avis compte', $corps));
    }

    public static function envoyerCreationCompteEmploye(string $to, string $prenom): bool
    {
        $corps = '<p>Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Un compte employé Vite &amp; Gourmand a été créé pour vous, avec cet email comme identifiant. '
            . 'Pour des raisons de sécurité, votre mot de passe ne figure pas dans ce message : '
            . 'rapprochez-vous de l\'administrateur pour l\'obtenir.</p>';
        return self::send($to, 'Votre compte employé Vite & Gourmand', self::layout('Compte créé', $corps));
    }

    public static function envoyerContact(string $entrepriseEmail, string $nomVisiteur, string $emailVisiteur, string $titre, string $description): bool
    {
        $corps = '<p>Nouveau message depuis le formulaire de contact.</p>'
            . '<p><strong>De :</strong> ' . htmlspecialchars($emailVisiteur, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Titre :</strong> ' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) . '</p>';
        return self::send($entrepriseEmail, 'Nouveau message de contact : ' . $titre, self::layout('Formulaire de contact', $corps));
    }
}
