<?php
/**
 * clientzone - Formulář adresy/firmy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class AddressForm extends \Ease\TWB5\Form
{
    /**
     * Otázky.
     *
     * @var type
     */
    public $address = null;

    public function __construct($address)
    {
        $addressID     = $address->getMyKey();
        $this->address = $address;
        parent::__construct('address'.$addressID);

        $this->addInput(new \Ease\Html\InputTag('kod',
            $address->getDataValue('kod')), _('Code'));
        $this->addInput(new \Ease\Html\InputTag('nazev',
            $address->getDataValue('nazev')), _('Name'));

        if (strlen($address->getDataValue('email')) == 0) {
            $address->addStatusMessage(_('Email address is empty'), 'warning');
        }

        $this->addInput(new \Ease\Html\InputTag('email',
            $address->getDataValue('email')), _('Email'));

        $this->addItem(new \Ease\Html\InputHiddenTag('class',
            get_class($address)));
//        $this->addItem(new \Ease\Html\InputHiddenTag('enquiry_id', $address->getDataValue('enquiry_id')));

        $this->addItem(new \Ease\Html\DivTag(new \Ease\TWB5\SubmitButton(_('Uložit'),
            'success'), ['style' => 'text-align: right']));

        if (!is_null($addressID)) {
            $this->addItem(new \Ease\Html\InputHiddenTag($address->keyColumn,
                $addressID));
        }
    }
}