<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of LabelSwitches
 *
 * @author vitex
 */
class LabelSwitches extends \Ease\Html\DivTag
{

    /**
     * FlexiBee Label Switches
     *
     * @param \AbraFlexi\RW $flexibee
     * @param array $properties
     */
    public function __construct($flexibee, $properties = [])
    {
        $allLabels     = \AbraFlexi\Stitek::getAvailbleLabels($flexibee);
        $enabledLabels = \AbraFlexi\Stitek::getLabels($flexibee);
        if (empty($enabledLabels)) {
            $enabledLabels = [];
        }
        $enabledLabels = array_flip($enabledLabels);
        if (!isset($properties['id'])) {
            $properties['id'] = $flexibee->getEvidence().$flexibee->getMyKey().'labed';
        }
        parent::__construct(null, $properties);
        foreach ($allLabels as $code => $title) {
            $twbsw = $this->addItem(new \Ease\Html\Widgets\Toggle($code,
                array_key_exists($code, $enabledLabels), 1,
                ['onText' => $code, 'offText' => $title, 'onColor' => 'success',
                'offColor' => 'default', 'labelWidth' => 10, 'handleWidth' => 200
            ]));
            $twbsw->setTagProperties(['data-evidence' => $flexibee->getEvidence(),
                'data-record' => $flexibee->getMyKey()]);
        }
    }

    public function finalize(): void
    {
        parent::finalize();
        // TWB5 bootstrapization is automatic
        $this->addJavaScript('

$(\'#'.$this->getTagID().' input\').on(\'switchChange.bootstrapSwitch\', function(event, state) {

$.ajax({
   url: \'labed.php\',
        data: {
                evidence: $(this).attr("data-evidence"),
                record: $(this).attr("data-record"),
                label: $(this).attr("name"),
                state: state
        },
        error: function() {
            console.log("not saved");
        },

        success: function(data) {
            console.log("saved");
        },
            type: \'POST\'
        });
});
');
    }
}
