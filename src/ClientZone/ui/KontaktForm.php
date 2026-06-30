<?php
/**
 * clientzone - Uživatel.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class KontaktForm extends \Ease\TWB5\Form
{
    /**
     * Otázky.
     *
     * @var type
     */
    public $contact = null;

    public function __construct($contact)
    {
        $contactID     = $contact->getMyKey();
        $this->contact = $contact;
        parent::__construct('contact'.$contactID);

        $this->addInput(new \Ease\Html\InputTag('jmeno',
            $contact->getDataValue('jmeno')), _('Name'));
        $this->addInput(new \Ease\Html\InputTag('prijmeni',
            $contact->getDataValue('prijmeni')), _('Příjmení'));
        $this->addInput(new \Ease\Html\InputTag('email',
            $contact->getDataValue('email')), _('Email'));
        $this->addInput(new \Ease\Html\InputTag('tel',
            $contact->getDataValue('tel')), _('Phone'));
        $this->addInput(new \Ease\Html\InputTag('mobil',
            $contact->getDataValue('mobil')), _('Cell'));
        $this->addInput(new \Ease\Html\InputTextTag('username',
            $contact->getDataValue('username')), _('Login'));
        $this->addInput(new \Ease\Html\InputPasswordTag('password',
            $contact->getDataValue('password')), _('Password'));

        $this->addItem(new \Ease\Html\InputHiddenTag('firma',
            $contact->getDataValue('firma')));

        $this->addItem(new \Ease\Html\InputHiddenTag('class',
            get_class($contact)));
//        $this->addItem(new \Ease\Html\InputHiddenTag('enquiry_id', $contact->getDataValue('enquiry_id')));

        $this->addItem(new \Ease\Html\DivTag(new \Ease\TWB5\SubmitButton(_('Uložit'),
            'success'), ['style' => 'text-align: right']));

        if (!is_null($contactID)) {
            $this->addItem(new \Ease\Html\InputHiddenTag($contact->keyColumn,
                $contactID));
        }
    }
}