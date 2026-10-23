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
     * AbraFlexi contact e-mail property (Bricks default "mail" is wrong for Kontakt).
     */
    public ?string $mailColumn = 'email';

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
     * Prefer e-mail stored in session data — Bricks getUserEmail() hits
     * getKontakt() which is empty after unserialize.
     */
    public function getUserEmail(): string
    {
        foreach (['email', 'mail', $this->mailColumn ?? 'email'] as $column) {
            if ($column === null || $column === '') {
                continue;
            }
            $value = $this->getDataValue($column);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        $login = $this->getUserLogin();
        if ($login !== '' && filter_var($login, \FILTER_VALIDATE_EMAIL)) {
            return $login;
        }

        try {
            return parent::getUserEmail();
        } catch (\Throwable) {
            return '';
        }
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
     * True when the company is both customer and supplier (typVztahu.odberDodav).
     *
     * Such contacts may see received invoices (faktura-prijata) in Client Zone.
     */
    public function isSupplierCustomer(): bool
    {
        $typ = $this->getAdresar()->getDataValue('typVztahuK');
        if ($typ === null || $typ === '' || $typ === false) {
            return false;
        }

        return (string) $typ === 'typVztahu.odberDodav';
    }

    /**
     * AbraFlexi filter fragment restricting documents to this customer's company.
     */
    public function getFirmaCondition(): string
    {
        $adresar = $this->getAdresar();
        $kod = $adresar->getDataValue('kod');
        if (!empty($kod)) {
            return "firma = '".\AbraFlexi\Functions::code(
                \AbraFlexi\Functions::uncode((string) $kod),
            )."'";
        }

        $firmaId = $adresar->getMyKey();
        if (empty($firmaId)) {
            return 'firma = -1';
        }

        return is_numeric($firmaId) ? "firma = {$firmaId}" : "firma = '{$firmaId}'";
    }

    /**
     * Unpaid invoices (including not-yet-due), with reminder dates.
     *
     * @return list<array<string, mixed>>
     */
    public function getUnpaidInvoices(): array
    {
        $invoicer = $this->getInvoicer();
        $invoicer->defaultUrlParams['limit'] = 0;
        $invoicer->defaultUrlParams['order'] = 'datSplat@A';

        $conditions = $this->getFirmaCondition()
            .' AND storno eq false'
            ." AND (stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr')";

        $rows = $invoicer->getColumnsFromAbraFlexi(
            [
                'id',
                'kod',
                'datVyst',
                'datSplat',
                'sumCelkem',
                'zbyvaUhradit',
                'mena',
                'varSym',
                'stavUhrK',
                'datUp1',
                'datUp2',
            ],
            [$conditions],
        );

        return \is_array($rows) ? array_values($rows) : [];
    }

    /**
     * Orders that are not finished and not cancelled.
     *
     * @return list<array<string, mixed>>
     */
    public function getPendingOrders(): array
    {
        $orders = new \AbraFlexi\ObjednavkaPrijata();
        $orders->defaultUrlParams['limit'] = 0;
        $orders->defaultUrlParams['order'] = 'datVyst@D';

        $conditions = $this->getFirmaCondition()
            .' AND storno eq false'
            ." AND (stavUzivK is null OR (stavUzivK neq 'stavDoklObch.hotovo'"
            ." AND stavUzivK neq 'stavDoklObch.storno'))";

        $rows = $orders->getColumnsFromAbraFlexi(
            [
                'id',
                'kod',
                'datVyst',
                'sumCelkem',
                'mena',
                'stavUzivK',
                'popis',
                'poznam',
            ],
            [$conditions],
        );

        return \is_array($rows) ? array_values($rows) : [];
    }

    /**
     * Customer's contracts (smlouva) — the services they have subscribed to.
     *
     * @return list<array<string, mixed>>
     */
    public function getContracts(): array
    {
        $contracts = new \AbraFlexi\Smlouva();
        $contracts->defaultUrlParams['limit'] = 0;
        $contracts->defaultUrlParams['order'] = 'smlouvaOd@D';

        $rows = $contracts->getColumnsFromAbraFlexi(
            ['id', 'kod', 'nazev', 'smlouvaOd', 'typSml'],
            [$this->getFirmaCondition()],
        );

        return \is_array($rows) ? array_values($rows) : [];
    }

    /**
     * Unpaid received invoices (supplier role — awaiting settlement by us).
     *
     * @return list<array<string, mixed>>
     */
    public function getUnpaidReceivedInvoices(): array
    {
        if (!$this->isSupplierCustomer()) {
            return [];
        }

        $engine = new \AbraFlexi\FakturaPrijata();
        $engine->defaultUrlParams['limit'] = 0;
        $engine->defaultUrlParams['order'] = 'datSplat@A';

        $conditions = $this->getFirmaCondition()
            .' AND storno eq false'
            ." AND (stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr')";

        $rows = $engine->getColumnsFromAbraFlexi(
            [
                'id',
                'kod',
                'datVyst',
                'datSplat',
                'sumCelkem',
                'zbyvaUhradit',
                'mena',
                'stavUhrK',
            ],
            [$conditions],
        );

        return \is_array($rows) ? array_values($rows) : [];
    }

    /**
     * Relationship summary for the customer dashboard.
     *
     * @return array{
     *     companyName: string,
     *     companyCode: string,
     *     since: ?string,
     *     sinceLabel: ?string,
     *     totalSpent: float,
     *     invoiceCount: int,
     *     orderCount: int,
     *     documentCount: int,
     *     currency: string,
     *     unpaid: list<array<string, mixed>>,
     *     unpaidTotal: float,
     *     pendingOrders: list<array<string, mixed>>,
     *     isSupplierCustomer: bool,
     *     receivedInvoiceCount: int,
     *     receivedTotal: float,
     *     unpaidReceived: list<array<string, mixed>>,
     *     unpaidReceivedTotal: float
     * }
     */
    public function getDashboardSummary(): array
    {
        $adresar = $this->getAdresar();
        $firma = $this->getFirmaCondition();
        $currency = 'Kč';
        $isSupplierCustomer = $this->isSupplierCustomer();

        $sinceCandidates = [];
        $created = $adresar->getDataValue('createdDate');
        if (empty($created)) {
            $loaded = $adresar->getColumnsFromAbraFlexi(
                ['createdDate', 'nazev', 'kod'],
                ['id' => $adresar->getMyKey()],
            );
            if (!empty($loaded[0]['createdDate'])) {
                $created = $loaded[0]['createdDate'];
            }
            if (!empty($loaded[0]['nazev'])) {
                $adresar->setDataValue('nazev', $loaded[0]['nazev']);
            }
        }
        $createdIso = self::dateToIso($created);
        if ($createdIso !== null) {
            $sinceCandidates[] = $createdIso;
        }

        $invoicer = $this->getInvoicer();
        $invoicer->defaultUrlParams['limit'] = 0;
        $invoiceRows = $invoicer->getColumnsFromAbraFlexi(
            ['sumCelkem', 'datVyst', 'mena'],
            [$firma.' AND storno eq false'],
        );
        $totalSpent = 0.0;
        $invoiceCount = 0;
        if (\is_array($invoiceRows)) {
            foreach ($invoiceRows as $row) {
                $totalSpent += (float) ($row['sumCelkem'] ?? 0);
                ++$invoiceCount;
                $iso = self::dateToIso($row['datVyst'] ?? null);
                if ($iso !== null) {
                    $sinceCandidates[] = $iso;
                }
                if (!empty($row['mena'])) {
                    $currency = \AbraFlexi\Functions::uncode((string) $row['mena']);
                }
            }
        }

        $orderEngine = new \AbraFlexi\ObjednavkaPrijata();
        $orderEngine->defaultUrlParams['limit'] = 0;
        $orderRows = $orderEngine->getColumnsFromAbraFlexi(
            ['datVyst'],
            [$firma.' AND storno eq false'],
        );
        $orderCount = 0;
        if (\is_array($orderRows)) {
            foreach ($orderRows as $row) {
                ++$orderCount;
                $iso = self::dateToIso($row['datVyst'] ?? null);
                if ($iso !== null) {
                    $sinceCandidates[] = $iso;
                }
            }
        }

        $receivedInvoiceCount = 0;
        $receivedTotal = 0.0;
        $unpaidReceived = [];
        $unpaidReceivedTotal = 0.0;
        if ($isSupplierCustomer) {
            $receivedEngine = new \AbraFlexi\FakturaPrijata();
            $receivedEngine->defaultUrlParams['limit'] = 0;
            $receivedRows = $receivedEngine->getColumnsFromAbraFlexi(
                ['sumCelkem', 'datVyst', 'mena'],
                [$firma.' AND storno eq false'],
            );
            if (\is_array($receivedRows)) {
                foreach ($receivedRows as $row) {
                    $receivedTotal += (float) ($row['sumCelkem'] ?? 0);
                    ++$receivedInvoiceCount;
                    $iso = self::dateToIso($row['datVyst'] ?? null);
                    if ($iso !== null) {
                        $sinceCandidates[] = $iso;
                    }
                    if (!empty($row['mena']) && $currency === 'Kč') {
                        $currency = \AbraFlexi\Functions::uncode((string) $row['mena']);
                    }
                }
            }

            $unpaidReceived = $this->getUnpaidReceivedInvoices();
            foreach ($unpaidReceived as $row) {
                $unpaidReceivedTotal += (float) ($row['zbyvaUhradit'] ?? $row['sumCelkem'] ?? 0);
            }
        }

        sort($sinceCandidates);
        $since = $sinceCandidates[0] ?? null;

        $unpaid = $this->getUnpaidInvoices();
        $unpaidTotal = 0.0;
        foreach ($unpaid as $row) {
            $unpaidTotal += (float) ($row['zbyvaUhradit'] ?? $row['sumCelkem'] ?? 0);
        }

        return [
            'companyName' => (string) ($adresar->getDataValue('nazev') ?: $adresar->getDataValue('kod') ?: ''),
            'companyCode' => \AbraFlexi\Functions::uncode((string) ($adresar->getDataValue('kod') ?: '')),
            'since' => $since,
            'sinceLabel' => $since !== null
                ? (new \DateTimeImmutable($since))->format('d.m.Y')
                : null,
            'totalSpent' => $totalSpent,
            'invoiceCount' => $invoiceCount,
            'orderCount' => $orderCount,
            'documentCount' => $invoiceCount + $orderCount + $receivedInvoiceCount,
            'currency' => $currency === 'CZK' ? 'Kč' : $currency,
            'unpaid' => $unpaid,
            'unpaidTotal' => $unpaidTotal,
            'pendingOrders' => $this->getPendingOrders(),
            'isSupplierCustomer' => $isSupplierCustomer,
            'receivedInvoiceCount' => $receivedInvoiceCount,
            'receivedTotal' => $receivedTotal,
            'unpaidReceived' => $unpaidReceived,
            'unpaidReceivedTotal' => $unpaidReceivedTotal,
        ];
    }

    /**
     * Normalize AbraFlexi date value to Y-m-d or null.
     */
    public static function dateToIso(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with(strtolower($raw), 'null')) {
            return null;
        }
        try {
            return (new \DateTimeImmutable($raw))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Format AbraFlexi date for display (d.m.Y).
     */
    public static function formatDate(mixed $value): ?string
    {
        $iso = self::dateToIso($value);

        return $iso !== null
            ? (new \DateTimeImmutable($iso))->format('d.m.Y')
            : null;
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
