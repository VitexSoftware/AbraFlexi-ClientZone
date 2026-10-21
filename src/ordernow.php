<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Finish cart checkout — create documents and show PDF embed.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer('login.php', _('Please sign in to finish your order'));

$oPage->addToHeader(new ui\PageTop(_('Order')));

$order = new Order($_SESSION['cart'] ?? []);
$finished = $order->finishOrder();

$shown = 0;

if ($finished !== []) {
    $wrap = $oPage->container->addItem(new \Ease\Html\DivTag(null, [
        'class' => 'cz-order-result',
    ]));
    $wrap->addItem(new \Ease\Html\DivTag(_('Checkout'), ['class' => 'eyebrow']));
    $wrap->addItem(new \Ease\Html\H1Tag(_('Order Accepted')));

    foreach ($finished as $document) {
        // After loadFromAbraFlexi() / attachments lastResponseCode is no longer 201
        if (empty($document->getMyKey())) {
            $order->addStatusMessage(
                sprintf(_('Document creation failed (%s)'), $document->getEvidence()),
                'error',
            );
            continue;
        }

        $_SESSION['cart'] = [];
        ++$shown;

        $kod = (string) ($document->getDataValue('kod') ?? $document->getMyKey());
        $type = (string) ($document->getDataValue('poznam') ?: _('Document'));
        $order->addStatusMessage(sprintf(_('Order %s was accepted'), $kod), 'success');

        $suppressMails = filter_var(
            \Ease\Shared::cfg('SUPPRESS_EMAILS', false),
            \FILTER_VALIDATE_BOOLEAN,
        );
        $emails = array_filter([
            $order->customer->getAdresar()->getDataValue('email'),
            $order->customer->getKontakt()->getDataValue('email'),
        ]);
        $email = implode(',', array_unique($emails));
        if (!$suppressMails && $email !== '' && method_exists($document, 'sendByMail')) {
            $prevThrow = $document->throwException;
            $document->throwException = false;
            try {
                if ($document->sendByMail(
                    $email,
                    $type.' '.$kod,
                    sprintf(_('%s for your order'), $type),
                )) {
                    $document->addStatusMessage(
                        sprintf(_('Proforma was sent to %s'), $email),
                        'success',
                    );
                } else {
                    $reason = '';
                    if (\is_array($document->lastResult) && !empty($document->lastResult['message'])) {
                        $reason = (string) $document->lastResult['message'];
                    }
                    $document->addStatusMessage(
                        sprintf(
                            _('Document created, but e-mail to %s was not sent (%s)'),
                            $email,
                            $reason !== '' ? $reason : _('mail disabled or failed'),
                        ),
                        'warning',
                    );
                }
            } catch (\AbraFlexi\Exception $exc) {
                $document->addStatusMessage(
                    sprintf(_('Document created, but e-mail failed: %s'), $exc->getMessage()),
                    'warning',
                );
            } finally {
                $document->throwException = $prevThrow;
            }
        }

        $panel = $wrap->addItem(new \Ease\TWB5\Panel(
            $type.' '.$kod,
            'info',
            new \AbraFlexi\ui\TWB5\EmbedResponsivePDF($document),
            new ui\DownloadInvoiceButton($document),
        ));
        $panel->addTagClass('cz-order-doc');
    }
}

if ($shown === 0) {
    $order->addStatusMessage(
        empty($finished) ? _('The order is empty') : _('No document was created'),
        'warning',
    );
    $oPage->container->addItem(new \Ease\TWB5\LinkButton(
        'cart.php',
        _('Back to cart'),
        'secondary',
    ));
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
