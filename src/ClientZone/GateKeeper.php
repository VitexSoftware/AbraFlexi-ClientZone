<?php
/**
 * clientzone - GateKeeper
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Description of GateKeeper
 *
 * @author vitex
 */
class GateKeeper extends \Ease\Sand
{

    /**
     * Is document accessible by user ?
     *
     * @param \AbraFlexi\RO $document FlexiBee documnet
     * @param Customer|User|\Ease\Anonym $user Current User
     *
     * @return boolean
     */
    public static function isAccessibleBy($document, $user)
    {
        $result = null;
        switch (get_class($user)) {
            case 'ClientZone\User': //Admin
                $result = true;
                break;
            case 'ClientZone\Customer': //Customer
                $result = (self::getDocumentCompany($document) == self::getCustomerCompany($user));
                break;
            case 'Ease\Anonym': //Anonymous
                $result = false;
                break;
        }
        return $result;
    }

    /**
     * Get Company code for document
     *
     * @param \AbraFlexi\RO $document
     *
     * @return string documnent code
     */
    public static function getDocumentCompany($document)
    {
        return $document->getDataValue('firma') ? \AbraFlexi\RO::uncode(
                $document->getDataValue('firma')) : null;
    }

    /**
     * Obtain customer company code
     *
     * @param Customer $customer
     * 
     * @return int
     */
    public static function getCustomerCompany($customer)
    {
        return $customer->adresar->getDataValue('kod') ? \AbraFlexi\RO::uncode($customer->adresar->getDataValue('kod'))
                : null;
    }
}
