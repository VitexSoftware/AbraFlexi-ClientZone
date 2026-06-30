<?php

namespace ClientZone\ui;

/**
 * Description of htmlInvoice
 *
 * @author vitex
 */
class HtmlInvoice extends \Ease\Container
{

    /**
     * Gives you html FlexiBee invoice
     * 
     * @param \AbraFlexi\RO $invoice
     */
    public function __construct($invoice)
    {
        $pdfFile = $invoice->downloadInFormat('pdf',
            '/var/lib/clientzone/tmp/');
        if (strlen($pdfFile)) {
            \Gufy\PdfToHtml\Config::set('pdftohtml.output',
                '/var/lib/clientzone/tmp');
            $pdf = new \Gufy\PdfToHtml\Pdf($pdfFile);
            preg_match("/<body[^>]*>(.*?)<\/body>/is", $pdf->html(),
                $initialContent);
        }
        parent::__construct($initialContent);
        //   \Ease\WebPage::singleton()->includeCss('css/flexibee.css');
    }
}
