<?php

declare(strict_types=1);

/**
 * clientzone - Customer (AbraFlexi contact).
 *
 * @author vitex
 */

namespace ClientZone;

/**
 * ClientZone customer with optional AbraFlexi password hashing.
 */
class Customer extends \AbraFlexi\Bricks\Customer
{
    /**
     * Where to look for username.
     */
    public ?string $loginColumn = 'email';

    /**
     * Algorithms AbraFlexi accepts for contact password hashing.
     *
     * @var list<string>
     */
    private const HASH_ALGORITHMS = ['sha256', 'sha512', 'sha1', 'md5'];

    public function passwordCheck($plaintext)
    {
        return $this->getKontakt()->authenticate($this->getUserLogin(), (string) $plaintext);
    }

    /**
     * Legacy code accesses $customer->adresar / $customer->kontakt, but Bricks
     * keeps those properties private — expose them via getters.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'adresar' => $this->getAdresar(),
            'kontakt' => $this->getKontakt(),
            'invoicer' => $this->getInvoicer(),
            default => null,
        };
    }

    /**
     * Bricks tryToLogin uses uninitialized private $kontakt/$adresar — fix that.
     *
     * @param array<string, string> $formData
     */
    public function tryToLogin(array $formData): bool
    {
        $login = isset($formData[$this->loginColumn]) ? trim((string) $formData[$this->loginColumn]) : '';
        $password = isset($formData[$this->passwordColumn]) ? trim((string) $formData[$this->passwordColumn]) : '';

        if ($login === '') {
            $this->addStatusMessage(_('missing login'), 'error');

            return false;
        }
        if ($password === '') {
            $this->addStatusMessage(_('missing password'), 'error');

            return false;
        }

        $kontakt = $this->getKontakt();
        $prevThrow = $kontakt->throwException;
        $kontakt->throwException = false;

        try {
            if ($kontakt->authenticate($login, $password) !== true) {
                return false;
            }

            $kontakt->defaultUrlParams['detail'] = 'full';
            $kontakt->loadFromAbraFlexi([$this->loginColumn => $login]);
            if (empty($kontakt->getMyKey())) {
                $kontakt->loadFromAbraFlexi(['username' => $login]);
            }
            if (empty($kontakt->getMyKey())) {
                $kontakt->loadFromAbraFlexi(['email' => $login]);
            }
            if (empty($kontakt->getMyKey())) {
                $this->addStatusMessage(_('Contact not found after authentication'), 'error');

                return false;
            }

            $this->takeData($kontakt->getData() ?? []);

            return (bool) $this->loginSuccess();
        } finally {
            $kontakt->throwException = $prevThrow;
        }
    }

    /**
     * Prefer session-safe fields — Bricks getUserName() hits getKontakt()/getAdresar().
     */
    public function getUserLogin(): string
    {
        if (!empty($this->userLogin)) {
            return (string) $this->userLogin;
        }

        foreach ([$this->loginColumn, 'email', 'username'] as $column) {
            if ($column === null || $column === '') {
                continue;
            }
            $value = $this->getDataValue($column);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return '';
    }

    public function getUserName(): string
    {
        return $this->getUserLogin();
    }

    /**
     * Ease\User::__sleep() keeps only logged+data, so private $firma/$adresar
     * are lost after each request. Recover firma from session data (or reload
     * the contact by login) and load the address book again.
     */
    public function getAdresar(): \AbraFlexi\Adresar
    {
        if ($this->getFirma() === null || $this->getFirma() === '') {
            $firma = $this->getDataValue('firma');
            if (empty($firma)) {
                $login = $this->getUserLogin();
                if ($login !== '') {
                    // Fresh Kontakt — getKontakt() would call getAdresar() (recursion).
                    $kontakt = new \AbraFlexi\Kontakt();
                    $kontakt->loadFromAbraFlexi([$this->loginColumn => $login]);
                    $firma = $kontakt->getDataValue('firma');
                    if (!empty($firma)) {
                        $this->setDataValue('firma', $firma);
                    }
                }
            }
            if (!empty($firma)) {
                $this->setFirma($firma);
            }
        }

        $adresar = parent::getAdresar();

        if (
            (empty($adresar->getMyKey()) || empty($adresar->getExternalIDs()))
            && !empty($this->getFirma())
        ) {
            $adresar->loadFromAbraFlexi($this->getFirma());
        }

        return $adresar;
    }

    /**
     * Persist firma + login into session data after parent marks the user logged in.
     * setFirma() must run after parent::loginSuccess() — it resets $kontakt.
     */
    public function loginSuccess()
    {
        $kontakt = $this->getKontakt();
        $firma = $kontakt->getDataValue('firma');
        foreach (['email', 'username', 'jmeno', 'prijmeni'] as $key) {
            $val = $kontakt->getDataValue($key);
            if ($val !== null && $val !== '') {
                $this->setDataValue($key, $val);
            }
        }
        if (!empty($firma)) {
            $this->setDataValue('firma', $firma);
        }

        $result = parent::loginSuccess();

        if (!empty($firma)) {
            $this->setFirma($firma);
            $this->getAdresar()->loadFromAbraFlexi($firma);
        }

        return $result;
    }

    /**
     * Whether contact passwords should be stored hashed in AbraFlexi.
     */
    public static function isPasswordHashEnabled(): bool
    {
        return self::passwordHashAlgorithm() !== null;
    }

    /**
     * Hash algorithm from ABRAFLEXI_PASSWORD_HASH, or null for plaintext.
     *
     * Empty / false / none / plain → plaintext. Unknown values fall back to sha256.
     */
    public static function passwordHashAlgorithm(): ?string
    {
        $raw = strtolower(trim((string) \Ease\Shared::cfg('ABRAFLEXI_PASSWORD_HASH', '')));

        if ($raw === '' || \in_array($raw, ['0', 'false', 'no', 'none', 'off', 'plain', 'plaintext'], true)) {
            return null;
        }

        if (\in_array($raw, self::HASH_ALGORITHMS, true)) {
            return $raw;
        }

        return 'sha256';
    }

    /**
     * Fields for writing a contact password (honours ABRAFLEXI_PASSWORD_HASH / _SALT).
     *
     * When hashing is on, password is the digest of `salt:plaintext` and AbraFlexi
     * stores `hash:salt:digest`. Login still uses plaintext via authenticate().
     *
     * @return array{password: string, 'password@hash'?: string, 'password@salt'?: string}
     */
    public static function passwordWriteFields(string $plainPassword): array
    {
        $algo = self::passwordHashAlgorithm();

        if ($algo === null) {
            return ['password' => $plainPassword];
        }

        $salt = trim((string) \Ease\Shared::cfg('ABRAFLEXI_PASSWORD_SALT', ''));
        if ($salt === '') {
            $salt = bin2hex(random_bytes(8));
        }

        return [
            'password' => hash($algo, $salt.':'.$plainPassword),
            'password@hash' => $algo,
            'password@salt' => $salt,
        ];
    }

    /**
     * Change the user's password in AbraFlexi (plaintext or hashed per .env).
     *
     * @param mixed $newPassword New password
     * @param mixed $userID      User ID (uses current user if not provided)
     */
    public function passwordChange($newPassword, $userID = null): bool
    {
        if (empty($userID)) {
            $userID = $this->getUserID();
        }

        if (empty($userID)) {
            $this->addStatusMessage('Cannot change password: no user ID', 'error');

            return false;
        }

        $kontakt = $this->getKontakt();
        $kontakt->insertToAbraFlexi(array_merge([
            'id' => $userID,
            'username' => $this->getUserLogin(),
        ], self::passwordWriteFields((string) $newPassword)));

        if ($kontakt->lastResponseCode === 201) {
            $kontakt->addStatusMessage(_('Password set'), 'success');
            $kontakt->loadFromAbraFlexi();

            $loginValue = $this->getDataValue($this->loginColumn);
            $this->addStatusMessage(
                'PasswordChange: '.($loginValue ?? 'unknown').'@'.$userID,
                'debug',
            );

            if ((int) $userID === $this->getUserID()) {
                $this->setDataValue($this->passwordColumn, (string) $newPassword);
            }

            return true;
        }

        $kontakt->addStatusMessage(_('Password set failed'), 'warning');

        return false;
    }
}
