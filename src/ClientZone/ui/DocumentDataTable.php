<?php

declare(strict_types=1);

/**
 * Thin wrapper — AbraFlexi DataTable with ClientZone ajax endpoint.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone\ui;

/**
 * Document listing DataTable (orders / invoices).
 */
class DocumentDataTable extends \AbraFlexi\ui\DataTables\DataTable
{
    /**
     * @param \AbraFlexi\RO        $engine     OrderLister / InvoiceLister
     * @param array<string, mixed> $properties
     */
    public function __construct(\AbraFlexi\RO $engine, array $properties = [])
    {
        // No Buttons plugin on Debian — keep empty buttons (lfrtip dom).
        if (!\array_key_exists('buttons', $properties)) {
            $properties['buttons'] = false;
        }

        parent::__construct($engine, $properties);

        // Always hit ClientZone feeder (ignore any filter leaked into query).
        $this->ajax2db = 'abraflexidata.php?'.http_build_query([
            'class' => $engine::class,
        ]);
    }
}
