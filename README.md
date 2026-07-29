![Packaging: deb](https://img.shields.io/badge/packaging-.deb-red?logo=debian&logoColor=white)


FlexiBee ClientZone
===================

![ClientZone Logo](https://raw.githubusercontent.com/VitexSoftware/ClientZone/master/src/images/logo.png "Project Logo")

Klientská zóna vašeho FlexiBee. Aplikace taktéž umožňuje objednávání položek ceníku FlexiBee a následnou reakci na příchozí platbu

Administrátor označí v ceníku nabízené položky štítkem ESHOP a ACTIVE. Poté jsou tyto nabízeny k obejdnání:

![Nabídka](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-ClientZone/master/doc/Shop4FlexiBee-screenshot.png "Snímek obrazovky aplikace")

Po volbě položky je možné vyplnit detaily:

![Formulář](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-ClientZone/master/doc/Shop4FlexiBee-order-item-form.png "Formulář položky objednávky")

Objednané položky jsou schraňovány v košíku:

![Potvrzení](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-ClientZone/master/doc/Shop4FlexiBee-confirm-screenshot.png "Potvrzení obejdnávky")

Výsledkem je buď zálohová faktura, nebo objednávka ve FlexiBee:

![Objednáno](https://raw.githubusercontent.com/VitexSoftware/FlexiBee-ClientZone/master/doc/Shop4FlexiBee-order-done.png "Dokončená objednávka")


Vlastnosti
----------

 * Do aplikace je možné se zaregistrovat
 * Je možné si změnit heslo
 * Je možné si necha obnovit zapomenuté heslo
 * Je možné si objednat z nabízených produktů is možností jim vyplnit požadované parametry
 * Je možné si objednat libovolný produkt dle kódu
 * Objednané položky se leží do objednání v košíku uloženém v session
 * Po objednání jsou z položek vytvářeny typy dokladů: faktura, zálohová faktura a objednávka.
 * Klient vidí historii svých objednávek a jejich stav
 * Klient si může faktury/objednávky zobrazit jako pdf, stahnout jako isdoc či nechat zaslat mailem


Moduly pro zpracování změn evidencí
===================================

Načítají se ze složky ClientZone\whplugins např **FakturaPrijata.php** a jsou vždy potomky třídy **\ClientZone\WebHookHandler**

V modulu je možné předefinovat metody create() update() a delete() které se vykonávají při patřičné změně.

Moduly pro zpracování objednaných položek
=========================================

Načítají se ze složky ClientZone\orderplugins např **DomainOrg.php** a jsou vždy potomky třídy **\ClientZone\OrderPlugin**

Plugin může mít předefinovány tyto metody:

 * **formFields($form)**     - vykreslí formulář s položkami potřebnými pro objednání položky  
 * **controlFields($order)** - zkontroluje hodnoty odeslané formulářem
 * **processFields($order)** - zpracuje hodnoty odeslané formulářem
 * **settled()**             - vykonává se v případě že byla zaplacena faktura obsahující položku s kodem který ma plugin na starosti


Testovací adresa: [https://clientzonee.vitexsoftware.cz/]

Požadavky pro běh:
------------------

 * PHP 5 a vyšší s mysqli rozšířením
 * Ease framework 
 * FlexiPeeHP
 * SQL Databáze s podporou PDO

Instalace
---------

Pro instalaci je třeba:

 * databáze a přidané deb zdroje VitexSoftware

        wget -O - http://v.s.cz/info@vitexsoftware.cz.gpg.key|sudo apt-key add -
        echo deb http://v.s.cz/ stable main > /etc/apt/sources.list.d/ease.list
        apt update
        apt install clientzone

Docker
------

Image pro docker obsahuje jádro debianu a php-fpm na portu 9000

```
    <VirtualHost *:80>                                                                                                                                                                                                                          
        ServerName clientzone.vitexsoftware.cz                                                                                                                                                                                              
        DocumentRoot /usr/share/clientzone                                                                                                                                                                                                  
        ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://127.0.0.1:9001/usr/share/clientzone/$1                                                                                                                                                               
        DirectoryIndex /index.php index.php                                                                                                                                                                                                 
    </VirtualHost>                                                                                                                                                                                                                              
```                 

Konfigurace:
------------

Pokud není nastaven klíč **FLEXIBEE_URL** nebo klíč **CONFIGURED** není "yes" je 
provedeno přesměrování na stránku setup.php. Zde je formulář po jehož uložení se
vytvoří konfigurační soubor /etc/flexibee/clientzone.json.

Tento konfigurační soubor se aplikace snaží načíst:

```json
{                                                                                                                                          
    "EASE_APPNAME": "ClientZone",                                                                                                      
    "EASE_LOGGER": "syslog",                                                                                                       
    "SEND_MAILS_FROM": "shop@syourdomain.net",                                                                                                
    "EMAIL_FROM": "shop@yourdomain.net",                                                                                                                       
    "EASE_EMAILTO": "info@vitexsoftware.cz",                                                                    
    "SUPPRESS_EMAILS": "true",
    "ALLOW_REGISTER": "true",
    "SHOW_PRICELIST": "false",
    "PRICELIST_CATID": "0",
    "SEND_INFO_TO": "office@yourdomain.net",                                                                                                                       
    "DEBUG": "false"
}
```

  * **SUPPRESS_EMAILS** - Neodesílají se Emaily klientům
  * **EASE_EMAILTO**    - Komu se odesílají logy po vykonání skriptů
  * **SEND_INFO_TO**    - Komu se posílá info o nových registracích a objednávkách
  * **ALLOW_REGISTER**  - Povolit registraci nových klientů
  * **SHOW_PRICELIST**  - Zobrazit Ceník
  * **PRICELIST_CATID** - ID Zobrazené větve ceníku

Adminská oprávnění pro uživatele: **a:1:{s:5:"admin";s:4:"true";}** 



Informace pro vývojáře:
-----------------------

 * Aplikace je vyvíjena pod v NetBeans pod linuxem.
 * Dokumentace ApiGen se nalézá ve složce doc
 * Složka testing obsahuje testovací sady Selenium a PHPUnit a strukturu DB
 * Aktuální zdrojové kody: **git@github.com:VitexSoftware/ClientZone.git**


© 2017-2018 Vítězslav Dvořák / Vitex Software
