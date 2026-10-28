![Packaging: deb](https://img.shields.io/badge/packaging-.deb-red?logo=debian&logoColor=white)

AbraFlexi ClientZone
====================

![ClientZone Logo](src/images/clientzone-logo.svg "Project Logo")

Klientská zóna napojená na [AbraFlexi](https://www.abraflexi.eu/) (dříve FlexiBee).
Zákazníci si prohlížejí ceník, objednávají služby, spravují kontaktní údaje a
pracují s fakturami a objednávkami — bez přímého přístupu do AbraFlexi.

Administrátor označí v ceníku nabízené položky štítkem **ESHOP** a **AKTIVNI**.
Poté jsou tyto nabízeny k objednání v katalogu.

Vlastnosti
----------

 * Registrace, přihlášení, změna a obnova hesla
 * **Dashboard** po přihlášení: souhrn obchodního vztahu (zákazníkem od, celková
   útrata), neuhrazené faktury (včetně dat upomínek) a nevyřízené objednávky
 * Objednávka položek z ceníku s volitelnými parametry (order pluginy)
 * Košík uložený v session
 * Po objednání vznikají v AbraFlexi doklady (faktura / záloha / objednávka)
 * Historie objednávek a faktur (DataTables)
 * Zobrazení PDF, stažení ISDOC, odeslání dokladu e-mailem
 * GDPR správa kontaktních údajů (`mycontact.php`)
 * Hashování hesel kontaktů v AbraFlexi (volitelné přes `.env`)

Moduly pro zpracování objednaných položek
=========================================

Načítají se ze složky `ClientZone/orderplugins/` a jsou potomky `\ClientZone\OrderPlugin`.
Jádro obsahuje obecný plugin `Common.php`. Kreditové pluginy `VoIPcredit.php` a
`DomainCredit.php` jsou v balíčku `abraflexi-clientzone-spojenet` (zdroje v `src/ClientZone/orderplugins/`).

Plugin může předefinovat:

 * **formFields($form)** — formulář parametrů položky
 * **controlFields($order)** — validace odeslaných hodnot
 * **processFields($order)** — zpracování hodnot
 * **settled()** — po úhradě faktury s položkou, kterou plugin spravuje

Požadavky
---------

 * PHP 8.1+ (balíček cílí na PHP 8.4)
 * Debian balíčky: `php-spojenet-abraflexi`, Ease / TWB5 stack, `php-vitexsoftware-abraflexi-datatables`
 * AbraFlexi API (URL, login, heslo, firma)

Instalace (Debian)
------------------

```bash
wget -O /etc/apt/keyrings/vitexsoftware.gpg https://repo.vitexsoftware.com/keyring.gpg
echo "deb [signed-by=/etc/apt/keyrings/vitexsoftware.gpg] http://repo.vitexsoftware.com bookworm main" \
  > /etc/apt/sources.list.d/vitexsoftware.list
apt update
apt install abraflexi-clientzone
```

Aplikace se instaluje do `/usr/share/abraflexi-clientzone/` a je dostupná např. jako
`/clientzone/` (Apache alias z balíčku).

Konfigurace
-----------

Debconf / postinst zapíše `/etc/abraflexi-client-zone/.env`. Příklad klíčů:

```env
ABRAFLEXI_URL="https://flexibee.example.com:5434"
ABRAFLEXI_LOGIN="user"
ABRAFLEXI_PASSWORD="secret"
ABRAFLEXI_COMPANY="firma_s_r_o_"
ABRAFLEXI_PASSWORD_HASH="sha256"
ALLOW_REGISTER="true"
SHOW_PRICELIST="true"
WELCOME_CS="Objednejte domény, VoIP kredit a hosting online — ihned, bez čekání na zpětný telefonát."
WELCOME_EN="Order domains, VoIP credit and hosting online — instantly, without waiting for a callback."
PROD_CAT="1"
EASE_LOGGER="syslog"
EASE_EMAILTO="admin@example.com"
```

| Klíč | Význam |
|------|--------|
| `ALLOW_REGISTER` | Povolit registraci nových klientů |
| `REGISTER_USERNAME_IS_EMAIL` | Přihlašovací jméno musí být shodné s e-mailem (výchozí `true`) |
| `CLIENTZONE_URL` | Veřejná adresa aplikace pro odkazy v e-mailech, nutná pro ověření e-mailu |
| `REGISTER_VERIFY_EMAIL` | Při registraci ověřit e-mailovou schránku odkazem (výchozí `false`) |
| `SHOW_PRICELIST` | Zobrazit katalog na úvodní stránce |
| `WELCOME_<LL>` | Úvodní text na titulní stránce pro jazyk `LL` (`WELCOME_CS`, `WELCOME_DE`, …); fallback `WELCOME_EN`, pak výchozí text |
| `PROD_CAT` | ID větve ceníku |
| `ABRAFLEXI_PASSWORD_HASH` | Hash hesel kontaktů (`sha256` / `plaintext` / prázdné) |
| `SUPPRESS_EMAILS` | Potlačit odesílání e-mailů klientům |

Bezpečnost / role
-----------------

V ClientZone **neexistuje role administrátora**. Přihlášení jsou výhradně
kontakty AbraFlexi (`Customer`). Každý uživatel vidí jen doklady a údaje své
firmy (`GateKeeper` + filtr `firma` v API).

Správa portálu (API klíče, feature flags) probíhá přes SSH úpravou
`/etc/abraflexi-client-zone/.env` nebo přímo v AbraFlexi. Lokální tabulka
`users` / skript `create-admin.php` se již nepoužívají.

Překlady (i18n)
---------------

Gettext doména `clientzone`, lokalizace v `i18n/`:

```bash
# regenerace šablony a merge
find src -name '*.php' -print0 | xargs -0 xgettext \
  --language=PHP --from-code=UTF-8 --keyword=_ --keyword=gettext \
  --package-name=abraflexi-clientzone -o i18n/clientzone.pot
msgmerge --update i18n/cs_CZ/LC_MESSAGES/clientzone.po i18n/clientzone.pot
msgmerge --update i18n/en_US/LC_MESSAGES/clientzone.po i18n/clientzone.pot
msgfmt -o i18n/cs_CZ/LC_MESSAGES/clientzone.mo i18n/cs_CZ/LC_MESSAGES/clientzone.po
msgfmt -o i18n/en_US/LC_MESSAGES/clientzone.mo i18n/en_US/LC_MESSAGES/clientzone.po
```

Výchozí locale aplikace je `cs_CZ` (`src/includes/Init.php`).

Vývoj
-----

```bash
composer install
cp .env.example .env   # nebo vytvořit .env s AbraFlexi údaji
php -S localhost:8080 -t src
```

Zdrojáky: <https://github.com/VitexSoftware/AbraFlexi-ClientZone>

© 2017–2026 Vítězslav Dvořák / Vitex Software
