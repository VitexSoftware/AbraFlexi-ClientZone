<?php

declare(strict_types=1);

/**
 * Self-service contact editor for logged-in customers (GDPR).
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone\ui;

/**
 * Form for updating the customer's own AbraFlexi contact record.
 */
class MyContactForm extends \Ease\TWB5\Form
{
    /**
     * Editable AbraFlexi contact fields (whitelist).
     *
     * @var list<string>
     */
    public const EDITABLE = [
        'jmeno',
        'prijmeni',
        'email',
        'tel',
        'mobil',
        'titul',
        'titulZa',
        'popis',
    ];

    public function __construct(\AbraFlexi\Kontakt $kontakt)
    {
        parent::__construct([
            'name' => 'mycontact',
            'method' => 'POST',
            'class' => 'cz-mycontact-form',
            'autocomplete' => 'on',
        ]);

        $this->addItem(new \Ease\Html\InputHiddenTag('save_contact', '1'));

        $this->addInput(
            new \Ease\Html\InputTextTag('jmeno', (string) ($kontakt->getDataValue('jmeno') ?? ''), [
                'required' => 'required',
                'autocomplete' => 'given-name',
            ]),
            _('First name'),
        );
        $this->addInput(
            new \Ease\Html\InputTextTag('prijmeni', (string) ($kontakt->getDataValue('prijmeni') ?? ''), [
                'required' => 'required',
                'autocomplete' => 'family-name',
            ]),
            _('Surname'),
        );
        $this->addInput(
            new \Ease\Html\InputTextTag('titul', (string) ($kontakt->getDataValue('titul') ?? ''), [
                'autocomplete' => 'honorific-prefix',
            ]),
            _('Title (before)'),
        );
        $this->addInput(
            new \Ease\Html\InputTextTag('titulZa', (string) ($kontakt->getDataValue('titulZa') ?? ''), [
                'autocomplete' => 'honorific-suffix',
            ]),
            _('Title (after)'),
        );
        $this->addInput(
            new \Ease\Html\InputEmailTag('email', (string) ($kontakt->getDataValue('email') ?? ''), [
                'required' => 'required',
                'autocomplete' => 'email',
            ]),
            _('Email'),
            null,
            _('Used for login and notifications'),
        );
        $this->addInput(
            new \Ease\Html\InputTextTag('tel', (string) ($kontakt->getDataValue('tel') ?? ''), [
                'autocomplete' => 'tel',
            ]),
            _('Phone'),
        );
        $this->addInput(
            new \Ease\Html\InputTextTag('mobil', (string) ($kontakt->getDataValue('mobil') ?? ''), [
                'autocomplete' => 'tel',
            ]),
            _('Mobile'),
        );
        $this->addInput(
            new \Ease\Html\TextareaTag('popis', (string) ($kontakt->getDataValue('popis') ?? ''), [
                'rows' => '3',
                'class' => 'form-control',
            ]),
            _('Note'),
        );

        $actions = new \Ease\Html\DivTag(null, ['class' => 'cz-form-actions d-flex flex-wrap gap-2 mt-3']);
        $actions->addItem(new \Ease\TWB5\SubmitButton(_('Save contact details'), 'primary'));
        $actions->addItem(new \Ease\Html\ATag(
            'changepassword.php',
            _('Change password'),
            ['class' => 'btn btn-line'],
        ));
        $this->addItem($actions);
    }
}
