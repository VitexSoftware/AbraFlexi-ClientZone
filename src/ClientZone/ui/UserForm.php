<?php
/**
 * clientzone - Uživatel.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class UserForm extends \Ease\TWB5\Form
{
    /**
     * Otázky.
     *
     * @var type
     */
    public $user = null;

    public function __construct($user)
    {
        $userID     = $user->getMyKey();
        $this->user = $user;
        parent::__construct([
            'name' => 'user'.(string) $userID,
            'method' => 'POST',
        ]);

        $this->addInput(new \Ease\Html\InputTag('firstname',
            $user->getDataValue('firstname')), _('Name'));
        $this->addInput(new \Ease\Html\InputTag('lastname',
            $user->getDataValue('lastname')), _('Příjmení'));
        $this->addInput(new \Ease\Html\InputTag('email',
            $user->getDataValue('email')), _('Email'));
        $this->addInput(new \Ease\Html\InputTag('login',
            $user->getDataValue('login')), _('Přihlašovací jméno'));

        $this->addItem(new \Ease\Html\InputHiddenTag('class', get_class($user)));
//        $this->addItem(new \Ease\Html\InputHiddenTag('enquiry_id', $user->getDataValue('enquiry_id')));

        $this->addItem(new \Ease\Html\DivTag(new \Ease\TWB5\SubmitButton(_('Uložit'),
            'success'), ['style' => 'text-align: right']));

        if (!is_null($userID)) {
            $this->addItem(new \Ease\Html\InputHiddenTag($user->keyColumn,
                $userID));
        }
    }
}