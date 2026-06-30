<?php
/**
 * Description of SysPokladnaSelect.
 *
 * @author vitex
 */

namespace ClientZone\ui;

class PokladnaSelect extends \Ease\Html\Select
{
    /**
     * FlexiBee pokladny.
     *
     * @var SysPokladna
     */
    public $selector = null;

    /**
     * Výběr pokladen.
     *
     * @param string $name
     * @param array  $items
     * @param string $defaultValue
     * @param type   $itemsIDs
     * @param type   $properties
     */
    public function __construct($name, $items = null, $defaultValue = null,
                                $itemsIDs = false, $properties = null)
    {
        parent::__construct($name, $items, $defaultValue, $itemsIDs, $properties);
        $this->selector = new \AbraFlexi\Pokladna();
    }

    /**
     * Naplní výběr pokladen možnostmi.
     *
     * @param type $items
     *
     * @return type
     */
    public function addItems($items)
    {
        $kasy  = $this->selector->getColumnsFromFlexibee(
            [
            $this->selector->nameColumn, $this->selector->keyColumn,
            ], null, $this->selector->nameColumn, $this->selector->keyColumn
        );
        $items = [];
        foreach ($kasy as $kasaId => $kasaInfo) {
            $items[$kasaId] = $kasaInfo[$this->selector->nameColumn];
        }

        return parent::addItems($items);
    }
}