<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Product detail: everything the customer needs to decide whether to buy it.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$product = ProductDetail::find((string) $oPage->getRequestValue('kod'));

if ($product === null) {
    http_response_code(404);
    $oPage->addToHeader(new ui\PageTop(_('Product not found')));
    $oPage->container->addItem(new \Ease\Html\PTag(_('This product is not in the catalog.'), ['class' => 'dim']));
    $oPage->container->addItem(new \Ease\Html\ATag('index.php#catalog', _('Back to the catalog'), ['class' => 'btn btn-line']));
    $oPage->addItem(new ui\PageBottom());
    $oPage->draw();

    exit;
}

// The name comes from AbraFlexi: neither the title nor the heading escapes it
$oPage->addToHeader(new ui\PageTop(htmlspecialchars($product['name'])));

$kind = $product['kind'];
$unitName = $product['unitName'];

$row = $oPage->container->addItem(new \Ease\Html\DivTag(null, ['class' => 'row g-4 cz-product-detail']));

// Picture
$left = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
$pricer = new \AbraFlexi\Cenik();
$pricer->setMyKey($product['id']);
$pricer->setDataValue('nazev', $product['name']);
$left->addItem(new ui\ProductImageThumbnail($pricer, ['class' => 'cz-product-img img-fluid']));

// Description, price, the way it is paid for
$right = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-8']));
$right->addItem(new \Ease\Html\DivTag(htmlspecialchars($product['code']), ['class' => 'cz-product-sku']));
$right->addItem(new \Ease\Html\H1Tag(htmlspecialchars($product['name'])));

$right->addItem(new \Ease\Html\SpanTag(
    BillingKind::label($kind),
    ['class' => 'badge fs-6 mb-3 '.BillingKind::badgeClass($kind)],
));

$priceLine = ProductDetail::formatPrice($product['priceWithVat']);

if ($kind === BillingKind::RECURRING && $unitName !== '') {
    $priceLine .= ' / '.$unitName;
}

$price = $right->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-product-price mb-3']));
$price->addItem(new \Ease\Html\SpanTag(htmlspecialchars($priceLine), ['class' => 'cz-price-main']));
$price->addItem(new \Ease\Html\SpanTag(
    htmlspecialchars(ProductDetail::formatPrice($product['priceWithoutVat'])).' '._('excl. VAT'),
    ['class' => 'cz-price-sub'],
));

$right->addItem(new \Ease\Html\DivTag(
    '<strong>'.htmlspecialchars(BillingKind::label($kind)).'</strong><br>'.htmlspecialchars(BillingKind::explanation($kind, $unitName)),
    ['class' => 'alert alert-secondary'],
));

if ($product['description'] !== '') {
    $right->addItem(new \Ease\Html\H3Tag(_('Description')));
    $right->addItem(new \Ease\Html\PTag(nl2br(htmlspecialchars($product['description']))));
}

// How the order goes on: a proforma invoice is issued for goods, fees and for what a plugin provisions
$plugins = ProductDetail::plugins();
$proforma = isset($plugins[$product['code']]) || $product['stockType'] !== 'typZasoby.sluzba';
$right->addItem(new \Ease\Html\H3Tag(_('What happens after you order')));
$right->addItem(new \Ease\Html\PTag($proforma
    ? _('We issue a proforma invoice for your order. It is dealt with after you pay it.')
    : _('Your order is received by our salesman, who will contact you about the details.')));

// Details
$right->addItem(new \Ease\Html\H3Tag(_('Details')));
$details = new \Ease\Html\TableTag(null, ['class' => 'table table-sm w-auto']);
$vat = $product['vat'] > 0 ? $product['vat'].' %' : '';
$facts = [
    _('Code') => $product['code'],
    _('Type') => $product['stockTypeLabel'],
    _('Invoiced') => BillingKind::label($kind),
    _('Unit') => $unitName,
    _('Price excl. VAT') => ProductDetail::formatPrice($product['priceWithoutVat']),
    _('VAT') => $vat,
    _('Price incl. VAT') => ProductDetail::formatPrice($product['priceWithVat']),
];

foreach ($facts as $name => $value) {
    if ($value !== '') {
        $details->addRowColumns(['<strong>'.htmlspecialchars((string) $name).'</strong>', htmlspecialchars((string) $value)]);
    }
}

$right->addItem($details);

// Order
$isAnon = get_class(\Ease\Shared::user()) === 'Ease\Anonym';
$orderUrl = 'orderform.php?service='.$product['service'];
$right->addItem(new \Ease\Html\ATag(
    $isAnon ? 'login.php?backurl='.urlencode($orderUrl) : $orderUrl,
    _('Order Now').' <span class="arrow">→</span>',
    ['class' => 'btn btn-primary btn-lg me-2'],
));
$right->addItem(new \Ease\Html\ATag('index.php#catalog', _('Back to the catalog'), ['class' => 'btn btn-line btn-lg']));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
