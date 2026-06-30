<?php

namespace ClientZone\ui;

/**
 * Description of OrderListingItem
 *
 * @author vitex
 */
class OrderListingItem extends \Ease\TWB5\Row
{

    public function __construct($itemdata, $evidence)
    {
        parent::__construct();
//        $this->addColumn(2,
//            new \Ease\Html\ATag('order.php?id='.$itemdata['id'],
//            $itemdata['kod']));

        $this->addColumn(2,
            new \Ease\Html\StrongTag(new \Ease\Html\ATag('getpdf.php?id='.$itemdata['id'].'&evidence='.$evidence.'&embed=true',
            $itemdata['kod'])));

        $this->addColumn(2,
            $itemdata['sumCelkem'].' '.str_replace('code:', '',
                $itemdata['mena']));
        $this->addColumn(1,
            isset($itemdata['datSplat']) ? \AbraFlexi\RO::flexiDateToDateTime($itemdata['datSplat'])->format('d.m.Y')
                    : '' );

        switch ($evidence) {
            case 'faktura-vydana':
                if (strstr($itemdata['stavUhrK'], 'stavUhr.uhrazeno')) {
                    $column2 = new \Ease\TWB5\Badge('success', _('Settled'));
                } else {
                    $column2 = self::dueLabel($itemdata['datSplat']);
                }
                break;
            case 'objednavka-prijata':
                if ($itemdata['stavUzivK'] == 'stavDoklObch.hotovo') {
                    $column2 = new \Ease\TWB5\Badge('success', _('Done'));
                } else {
                    $column2 = new \Ease\TWB5\Badge('warning',
                        _('Open').': '.str_replace('stavDoklObch.', '',
                            $itemdata['stavUzivK']));
                }
                break;
            default :
                $column2 = '';

                break;
        }

        $this->addColumn(2, $column2);

        $links = [
            new \Ease\Html\ATag('document.php?id='.$itemdata['id'].'&embed=true&evidence='.$evidence,
                new \Ease\Html\ImgTag('images/oko.svg', _('Show'),
                ['height' => '20px']), 'info', ['title' => _('Show')])
            ,
            new \Ease\Html\ATag('getpdf.php?id='.$itemdata['id'].'&evidence='.$evidence,
                new \Ease\Html\ImgTag('images/download.svg', _('Download'),
                ['height' => '20px']), 'success', ['title' => _('Download')])
            ,
            new \Ease\Html\ATag('maildocument.php?id='.$itemdata['id'].'&evidence='.$evidence,
                new \Ease\Html\ImgTag('images/email.svg', _('Send'),
                ['height' => '20px']), 'success', ['title' => _('Send')])
        ];

        if ($evidence == 'faktura-vydana') {
            $links[] = new \Ease\Html\ATag('getisdoc.php?id='.$itemdata['id'].'&evidence='.$evidence,
                new \Ease\Html\ImgTag('images/ISDOC.png', _('ISDOC'),
                ['height' => '20px']), 'success', ['title' => _('ISDOC')]);
        }

        switch (get_class(\Ease\Shared::user())) {
            case 'ClientZone\ClientZone\User':
                $links[] = new \Ease\Html\ATag(constant('FLEXIBEE_URL').'/c/'.constant('FLEXIBEE_COMPANY').'/'.$evidence.'/'.$itemdata['id'],
                    new \Ease\Html\ImgTag('images/flexibee.png', _('FlexiBee'),
                    ['height' => '20px']), 'success', ['title' => _('FlexiBee')]);

                $links[] = new \Ease\Html\ATag('adresar.php?id='.$itemdata['firma'],
                    new \Ease\Html\ImgTag('images/company.svg', _('Company'),
                    ['height' => '20px', 'title' => $itemdata['firma@showAs']]),
                    'success', ['title' => _('Company')]);
                break;
        }

        $this->addColumn(2, $links);
    }

    static function price($data)
    {
        switch ($data['']) {
            case '':
                break;
        }


        return new \Ease\TWB5\Badge($type, $content);
    }

    /**
     * 
     * @param DateTime|string $date
     * 
     * @return \Ease\TWB5\Badge
     */
    static function dueLabel($date)
    {
        $days = \AbraFlexi\FakturaVydana::overdueDays($date);
        if ($days < 0) {
            $type = 'success';
            $msg  = sprintf(_(' %s days to due'),
                new \Ease\TWB5\Badge(abs($days)));
        } else {
            $msg = sprintf(_(' %s days after due'),
                new \Ease\TWB5\Badge(abs($days)));
            if ($days > 14) {
                $type = 'danger';
            } else {
                $type = 'warning';
            }
        }
        return new \Ease\TWB5\Badge($type, $msg);
    }

}
