<?php

/**
 * clientzone - Přehled záznamu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
namespace ClientZone;

class RecordShow extends \Ease\TWB5\Card
{
    /**
     * Zobrazí přehled záznamu.
     *
     * @param ABBase $recordObject
     */
    public function __construct($recordObject)
    {
        parent::__construct();

        $row = new \Ease\TWB5\Row();

        $this->addItem(new \Ease\Html\H3Tag($recordObject->getName()));

        $recordObject->setData(
                $recordObject->htmlizeRow($recordObject->getData())
        );

        foreach ($recordObject->keywordsInfo as $keyword => $kinfo) {
            if ($keyword == $recordObject->nameColumn) {
                continue;
            }
            if (isset($kinfo['title'])) {
                $def = new \Ease\Html\DlTag();
                $def->addDef(
                        $kinfo['title'], $recordObject->getDataValue($keyword)
                );
                $row->addItem(new \Ease\TWB5\Col(4, $def));
            }
        }

        $this->addItem($row);
    }
}
