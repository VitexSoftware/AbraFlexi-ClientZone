<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of LMSIDLoginForm
 *
 * @author vitex
 */
class FlexiBeeLoginForm extends \Ease\TWB5\Form
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'lmslogin',
            'action' => 'flexibeelogin.php',
            'method' => 'POST',
        ]);
        $this->addInput(new \Ease\Html\InputTextTag('username'), _('Login'),
            _('your email address in most of cases'));
        $this->addInput(new \Ease\Html\InputPasswordTag('password'), _('Login'),
            _('Password you recivied by email'));
        $this->addItem(new \Ease\TWB5\SubmitButton(_('Sign In')));
    }

    public function finalize(): void
    {
        $this->addStatusMessage(_('Please enter your Login and Password'),
            'info');
        parent::finalize();
    }

}
