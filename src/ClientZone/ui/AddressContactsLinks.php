<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of AddressContactsLinks
 *
 * @author vitex
 */
class AddressContactsLinks extends \Ease\Container
{

    /**
     * Show Links to Address' contacts
     *
     * @param \AbraFlexi\Adresar $address Addres Object
     */
    public function __construct(\AbraFlexi\Adresar $address)
    {
        parent::__construct();
        $contacts = $address->getDataValue('kontakty');
        if (count($contacts)) {
            foreach ($contacts as $contactInfo) {
                $jmeno   = trim($contactInfo['titul'].' '.$contactInfo['jmeno'].' '.$contactInfo['prijmeni'].' '.$contactInfo['titulZa']);
                $telefon = trim($contactInfo['tel'].' '.$contactInfo['mobil']);
                $email   = $contactInfo['email'];
                $this->addItem(new \Ease\TWB5\LinkButton('kontakt.php?id='.$contactInfo['id'],
                    trim($jmeno.' '.$email.' '.$telefon), 'info'));
            }
        }
        $this->addItem(new \Ease\TWB5\LinkButton('kontakt.php?firma='.$address->getDataValue('id').'&email='.$address->getDataValue('email'),
            '<i class="fa fa-plus"></i>'.' '._('New Contact'), 'success'));
    }
}
