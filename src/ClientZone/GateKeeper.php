<?php

declare(strict_types=1);

/**
 * clientzone - GateKeeper — document access only for owning customer.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Ensures a logged-in customer may only access documents of their own company.
 * No elevated / admin bypass exists on purpose.
 */
class GateKeeper extends \Ease\Sand
{
    /**
     * Is document accessible by user?
     *
     * @param \AbraFlexi\RO              $document AbraFlexi document
     * @param Customer|\Ease\Anonym|mixed $user     Current user
     */
    public static function isAccessibleBy($document, $user): bool
    {
        if (!$user instanceof Customer) {
            return false;
        }

        $docCompany = self::getDocumentCompany($document);
        $custCompany = self::getCustomerCompany($user);

        if ($docCompany === null || $custCompany === null || $docCompany === '' || $custCompany === '') {
            return false;
        }

        return strcasecmp($docCompany, $custCompany) === 0;
    }

    /**
     * Company code on the document (without code: prefix).
     *
     * @param \AbraFlexi\RO $document
     */
    public static function getDocumentCompany($document): ?string
    {
        $firma = $document->getDataValue('firma');
        if ($firma === null || $firma === '' || $firma === false) {
            return null;
        }

        return \AbraFlexi\Functions::uncode((string) $firma);
    }

    /**
     * Logged-in customer's company code (without code: prefix).
     */
    public static function getCustomerCompany(Customer $customer): ?string
    {
        $kod = $customer->getAdresar()->getDataValue('kod');
        if ($kod === null || $kod === '' || $kod === false) {
            return null;
        }

        return \AbraFlexi\Functions::uncode((string) $kod);
    }
}
