<?php

declare(strict_types=1);

/**
 * clientzone - SetupForm.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * AbraFlexi connection setup form with additional ClientZone options.
 */
class SetupForm extends \AbraFlexi\ui\TWB5\ConnectionForm
{
    public string $urlField = 'ABRAFLEXI_URL';
    public string $usernameField = 'ABRAFLEXI_LOGIN';
    public string $passwordField = 'ABRAFLEXI_PASSWORD';
    public string $companyField = 'ABRAFLEXI_COMPANY';

    public function __construct(string $formAction = 'setup.php', string $formMethod = 'post', array $tagProperties = [])
    {
        $options = [
            'ABRAFLEXI_URL'      => \Ease\Shared::cfg('ABRAFLEXI_URL', ''),
            'ABRAFLEXI_LOGIN'    => \Ease\Shared::cfg('ABRAFLEXI_LOGIN', ''),
            'ABRAFLEXI_PASSWORD' => \Ease\Shared::cfg('ABRAFLEXI_PASSWORD', ''),
            'ABRAFLEXI_COMPANY'  => \Ease\Shared::cfg('ABRAFLEXI_COMPANY', ''),
        ];

        parent::__construct($options, array_merge(['action' => $formAction, 'method' => $formMethod], $tagProperties));

        $this->addInput(
            new \Ease\Html\InputEmailTag('SEND_MAILS_FROM'),
            _('Send mails From'),
            'clientzone@'.$_SERVER['HTTP_HOST'],
            _('Outgoing emails sender address')
        );
        $this->addInput(
            new \Ease\Html\InputEmailTag('SEND_INFO_TO'),
            _('Send info To'),
            _('Your Address').'@'.$_SERVER['HTTP_HOST'],
            _('Where to send info about new orderers')
        );
        $this->addInput(
            new \Ease\Html\Widgets\Toggle('SUPPRESS_EMAILS'),
            _('Suppress Emails'),
            null,
            _('Do not send any emails')
        );
        $this->addInput(
            new \Ease\Html\Widgets\Toggle('ALLOW_REGISTER'),
            _('Allow Sign Up'),
            null,
            _('Customer can create new account')
        );
        $this->addInput(
            new \Ease\Html\Widgets\Toggle('SHOW_PRICELIST'),
            _('Show Pricelist'),
            null,
            _('Show pricelist of items with "eshop" flag or label')
        );
        $this->addInput(
            new \Ease\Html\SelectTag('ABRAFLEXI_PASSWORD_HASH', [
                'plain' => _('Plaintext'),
                'sha256' => 'sha256',
                'sha512' => 'sha512',
                'sha1' => 'sha1',
                'md5' => 'md5',
            ], \Ease\Shared::cfg('ABRAFLEXI_PASSWORD_HASH', 'sha256') ?: 'plain'),
            _('Contact password hashing'),
            'sha256',
            _('Store AbraFlexi contact passwords hashed (recommended) or as plaintext')
        );
    }
}
