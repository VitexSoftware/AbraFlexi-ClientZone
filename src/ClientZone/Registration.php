<?php

declare(strict_types=1);

/**
 * clientzone - Customer registration logic.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

use Ease\Shared;

/**
 * Registration of a new login for an AbraFlexi contact (/kontakt/).
 *
 * One company (/adresar/) may have many contacts, each with its own username
 * and password. Registration starts with the e-mail address:
 *
 *  - STATE_KNOWN:  e-mail belongs to a contact without login → only credentials are added
 *  - STATE_TAKEN:  e-mail belongs to a contact which already has a username → refuse
 *  - STATE_NEW:    e-mail is unknown → company (adresar) and contact are created
 *
 * AbraFlexi never returns the contact password, so "has login" means
 * a non-empty username.
 */
class Registration
{
    public const STATE_NEW = 'new';
    public const STATE_KNOWN = 'known';
    public const STATE_TAKEN = 'taken';

    /**
     * Pending e-mail verification link lifetime in seconds.
     */
    public const TOKEN_TTL = 86400;

    private const CIPHER = 'aes-256-gcm';

    /**
     * Username has to be the e-mail address (REGISTER_USERNAME_IS_EMAIL, default true).
     */
    public static function usernameIsEmail(): bool
    {
        return self::flag('REGISTER_USERNAME_IS_EMAIL', true);
    }

    /**
     * E-mail ownership must be verified first (REGISTER_VERIFY_EMAIL, default false).
     */
    public static function verifyEmail(): bool
    {
        return self::flag('REGISTER_VERIFY_EMAIL', false);
    }

    /**
     * Classify an e-mail address against known contacts.
     *
     * @return array{state: string, contact: ?array<string, mixed>}
     */
    public static function lookup(string $email): array
    {
        $rows = self::contactsBy('email', $email);

        if ($rows === []) {
            return ['state' => self::STATE_NEW, 'contact' => null];
        }

        foreach ($rows as $row) {
            if (empty($row['username'])) {
                return ['state' => self::STATE_KNOWN, 'contact' => $row];
            }
        }

        return ['state' => self::STATE_TAKEN, 'contact' => $rows[0]];
    }

    /**
     * Is the username used as username or e-mail by any contact (except the given one)?
     */
    public static function usernameTaken(string $username, mixed $exceptId = null): bool
    {
        // The e-mail column counts too: a username equal to somebody else's e-mail
        // would let the owner of that username sign in as that contact.
        foreach (['username', 'email'] as $column) {
            foreach (self::contactsBy($column, $username) as $row) {
                if ($exceptId === null || (string) $row['id'] !== (string) $exceptId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Write credentials (and the company for unknown e-mails) to AbraFlexi.
     *
     * $data keys: email, username, state, contactId (STATE_KNOWN),
     * nazev, ic, dic, tel (STATE_NEW), passwordFields (output of
     * Customer::passwordWriteFields()).
     *
     * @param array<string, mixed> $data
     *
     * @return ?Customer created / updated contact, null on failure
     */
    public static function complete(array $data): ?Customer
    {
        $email = (string) $data['email'];
        $username = (string) $data['username'];

        // Re-check, the verification link may be used twice or the state may have changed.
        $current = self::lookup($email);

        if ($current['state'] === self::STATE_TAKEN || self::usernameTaken($username, $data['contactId'] ?? null)) {
            return null;
        }

        $customer = new Customer();

        if ($data['state'] === self::STATE_KNOWN) {
            if ($current['state'] !== self::STATE_KNOWN) {
                return null;
            }

            $customer->kontakt->insertToAbraFlexi(array_merge([
                'id' => $data['contactId'],
                'username' => $username,
            ], $data['passwordFields']));

            return $customer->kontakt->lastResponseCode === 201 ? $customer : null;
        }

        if ($current['state'] !== self::STATE_NEW) {
            return null;
        }

        $customer->adresar->setData([
            'email' => $email,
            'nazev' => $data['nazev'],
            'ic' => $data['ic'] ?? '',
            'dic' => $data['dic'] ?? '',
            'tel' => $data['tel'] ?? '',
        ]);

        if (!$customer->adresar->sync()) {
            $customer->addStatusMessage(_('Error saving Company'), 'error');

            return null;
        }

        $customer->addStatusMessage(_('Company registered'), 'success');

        $customer->kontakt->setData(array_merge([
            'email' => $email,
            'username' => $username,
            'tel' => $data['tel'] ?? '',
            'firma' => $customer->adresar,
        ], $data['passwordFields']));

        if (!$customer->kontakt->sync()) {
            $customer->addStatusMessage(_('Error saving Contact'), 'error');

            return null;
        }

        $customer->addStatusMessage(_('User account created'), 'success');

        return $customer;
    }

    /**
     * Encrypted and authenticated token carrying the registration data.
     *
     * @param array<string, mixed> $data
     */
    public static function makeToken(array $data): string
    {
        $data['exp'] = time() + self::TOKEN_TTL;
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(json_encode($data, \JSON_THROW_ON_ERROR), self::CIPHER, self::tokenKey(), \OPENSSL_RAW_DATA, $iv, $tag);

        return rtrim(strtr(base64_encode($iv.$tag.$cipher), '+/', '-_'), '=');
    }

    /**
     * @return ?array<string, mixed> null when the token is damaged or expired
     */
    public static function parseToken(string $token): ?array
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);

        if ($raw === false || \strlen($raw) < 29 || !self::tokenSecretConfigured()) {
            return null;
        }

        $plain = openssl_decrypt(substr($raw, 28), self::CIPHER, self::tokenKey(), \OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));

        if ($plain === false) {
            return null;
        }

        $data = json_decode($plain, true);

        return \is_array($data) && ($data['exp'] ?? 0) >= time() ? $data : null;
    }

    /**
     * Send the verification link; false when sending is suppressed or failed.
     */
    public static function sendVerification(string $email, string $token, string $baseUrl): bool
    {
        if (self::flag('SUPPRESS_EMAILS', false)) {
            return false;
        }

        $url = rtrim($baseUrl, '/').'/verifyemail.php?t='.$token;
        $mail = new \Ease\Mailer($email, Shared::appName().' — '._('Confirm your e-mail address'));
        $mail->setMailHeaders(['From' => (string) Shared::cfg('SEND_MAILS_FROM', Shared::cfg('EMAIL_FROM', ''))]);
        $mail->addItem(_('To finish the registration open this link:')."\n");
        $mail->addItem($url."\n");
        $mail->addItem("\n".sprintf(_('The link is valid for %d hours.'), self::TOKEN_TTL / 3600)."\n");

        return (bool) $mail->send();
    }

    /**
     * Is there a dedicated random REGISTRATION_SECRET (at least 32 characters)?
     */
    public static function tokenSecretConfigured(): bool
    {
        return \strlen(trim((string) Shared::cfg('REGISTRATION_SECRET'))) >= 32;
    }

    /**
     * Token key from the dedicated secret. It is deliberately not derived from the
     * AbraFlexi credentials: anybody holding a token could then guess them offline.
     */
    private static function tokenKey(): string
    {
        if (!self::tokenSecretConfigured()) {
            throw new \RuntimeException('REGISTRATION_SECRET is not configured');
        }

        return hash_hkdf('sha256', trim((string) Shared::cfg('REGISTRATION_SECRET')), 32, 'clientzone-registration-token');
    }

    /**
     * @return list<array<string, mixed>> contacts having the column equal to value
     */
    private static function contactsBy(string $column, string $value): array
    {
        $kontakt = new \AbraFlexi\Kontakt();
        $kontakt->throwException = false;
        $rows = $kontakt->getColumnsFromAbraFlexi(
            ['id', 'email', 'username', 'firma'],
            [$column => $value],
        );

        return \is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    private static function flag(string $key, bool $default): bool
    {
        $value = Shared::cfg($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
