<?php

declare(strict_types=1);

/**
 * clientzone - E-shop product catalog.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * Product grid for the public / customer shop.
 */
class Pricelist extends \Ease\Html\DivTag
{
    public ?\AbraFlexi\Cenik $pricer = null;

    /**
     * @param int|string $category AbraFlexi cenik subtree id
     * @param array      $labels   stitky codes (e.g. ESHOP, AKTIVNI)
     */
    public function __construct($category, array $labels = [])
    {
        parent::__construct(null, ['class' => 'cz-catalog', 'id' => 'catalog']);

        $order = new \ClientZone\OrderItem();
        $orderable = [];
        foreach ($order->plugins as $pluginName => $plugin) {
            $orderable[$plugin->productCode] = $pluginName;
        }

        if (count($labels)) {
            foreach ($labels as $lid => $label) {
                $labels[$lid] = "stitky='code:".$label."'";
            }
            $category .= ' and '.implode(' and ', $labels);
        }

        $this->pricer = new \AbraFlexi\Cenik();
        $items = $this->pricer->getColumnsFromAbraFlexi('info', [
            '(in subtree '.$category.')',
            'limit' => 0,
        ]);

        $head = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-catalog-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Our products'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Catalog')));
        $head->addItem(new \Ease\Html\PTag(
            sprintf(_('%s products'), count($items)),
            ['class' => 'dim cz-catalog-count'],
        ));

        if (!count($items)) {
            $this->addItem(new \Ease\Html\DivTag(
                _('No products are available right now.'),
                ['class' => 'cz-empty'],
            ));

            return;
        }

        $grid = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-product-grid']));

        foreach ($items as $item) {
            if (array_key_exists($item['kod'], $orderable)) {
                $item['service'] = $orderable[$item['kod']];
            } else {
                $item['service'] = 'Common&kod='.$item['kod'];
            }
            $grid->addItem($this->productCard($item));
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    public function productCard(array $item): \Ease\Html\DivTag
    {
        $this->pricer->setMyKey($item['id']);
        $this->pricer->setDataValue('nazev', $item['nazev'] ?? '');

        $priceEx = (float) ($item['cenaZaklBezDph'] ?? 0);
        $priceInc = (float) ($item['cenaZaklVcDph'] ?? round($priceEx * 1.21));

        $card = new \Ease\Html\DivTag(null, ['class' => 'cz-product reveal']);

        $media = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-product-media']));
        $media->addItem(new ProductImageThumbnail($this->pricer, [
            'class' => 'cz-product-img',
            'loading' => 'lazy',
        ]));

        $body = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-product-body']));
        $body->addItem(new \Ease\Html\DivTag(
            htmlspecialchars((string) ($item['kod'] ?? '')),
            ['class' => 'cz-product-sku'],
        ));
        $body->addItem(new \Ease\Html\H3Tag((string) ($item['nazev'] ?? '')));

        $price = $body->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-product-price']));
        $price->addItem(new \Ease\Html\SpanTag(
            $this->formatPrice($priceInc),
            ['class' => 'cz-price-main'],
        ));
        $price->addItem(new \Ease\Html\SpanTag(
            $this->formatPrice($priceEx).' '._('excl. VAT'),
            ['class' => 'cz-price-sub'],
        ));

        if (array_key_exists('service', $item)) {
            $isAnon = get_class(\Ease\Shared::user()) === 'Ease\Anonym';
            $orderUrl = $isAnon
                ? 'login.php?backurl='.urlencode('orderform.php?service='.$item['service'])
                : 'orderform.php?service='.$item['service'];
            $body->addItem(new \Ease\Html\ATag(
                $orderUrl,
                _('Order Now').' <span class="arrow">→</span>',
                ['class' => 'btn btn-primary cz-product-cta'],
            ));
        }

        return $card;
    }

    public function formatPrice(float $amount): string
    {
        return number_format($amount, $amount == floor($amount) ? 0 : 2, ',', ' ').' Kč';
    }
}
