<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Search in the catalog and in the documents of the customer.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$query = Searcher::sanitizeTerm((string) $oPage->getRequestValue('search'));

$oPage->addToHeader(new ui\PageTop(_('Search results').($query !== '' ? ': '.$query : '')));

$titles = [
    'pricelist' => _('Catalog'),
    'faktura-vydana' => _('My invoices'),
    'objednavka-prijata' => _('My orders'),
];

if (!Searcher::isSearchable($query)) {
    $oPage->container->addItem(new \Ease\Html\PTag(
        sprintf(_('Type at least %d characters to search.'), Searcher::MIN_LENGTH),
        ['class' => 'dim'],
    ));
} else {
    $results = (new Searcher())->search($query);

    if ($results === []) {
        $oPage->container->addItem(new \Ease\Html\PTag(
            sprintf(_('Nothing found for %s.'), '<strong>'.htmlspecialchars($query).'</strong>'),
            ['class' => 'dim'],
        ));
    }

    foreach ($results as $group => $rows) {
        $table = new \Ease\Html\TableTag(null, ['class' => 'table']);
        $table->addRowHeaderColumns([_('Code'), _('Name'), _('Info')]);

        foreach ($rows as $row) {
            $link = static fn (string $text): string => '<a href="'.htmlspecialchars($row['url']).'">'.htmlspecialchars($text).'</a>';
            $table->addRowColumns([$link($row['code']), $link($row['name']), htmlspecialchars($row['info'])]);
        }

        $oPage->container->addItem(new \Ease\TWB5\Panel(
            sprintf(_('%s: %d found'), $titles[$group] ?? $group, \count($rows)),
            'info',
            $table,
        ));
    }
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
