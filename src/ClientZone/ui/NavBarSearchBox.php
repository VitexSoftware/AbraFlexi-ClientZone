<?php

declare(strict_types=1);

/**
 * clientzone - Vyhledávací okénko hlavního menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class NavBarSearchBox extends \Ease\TWB5\Form
{
    /**
     * @param string $formName   form name
     * @param string $formAction search endpoint
     */
    public function __construct(string $formName = 'search', string $formAction = 'search.php')
    {
        parent::__construct(
            [
                'name' => $formName,
                'action' => $formAction,
                'method' => 'get',
                'class' => 'd-flex align-items-center ms-2 me-2',
                'role' => 'search',
            ],
            ['class' => 'input-group input-group-sm'],
        );

        $term = \Ease\WebPage::singleton()->getRequestValue('search', 'string');
        $this->addItem(new \Ease\Html\InputTextTag('search', $term, [
            'class' => 'form-control typeahead',
            'placeholder' => _('Search'),
            'aria-label' => _('Search'),
            'autocomplete' => 'off',
            'style' => 'min-width: 180px; max-width: 280px;',
        ]));
        $this->addItem(new \Ease\Html\ButtonTag(
            '<i class="fa fa-search" aria-hidden="true"></i>',
            ['type' => 'submit', 'class' => 'btn btn-line btn-sm', 'title' => _('Search')],
        ));
    }

    public function finalize(): void
    {
        parent::finalize();
        $page = \Ease\WebPage::singleton();
        $page->includeJavaScript('js/handlebars.js');
        $page->includeJavaScript('js/typeahead.bundle.js');
        $page->addJavaScript(<<<'JS'
var bestPictures = new Bloodhound({
    limit: 1000,
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: 'searcher.php?q=%QUERY',
      wildcard: '%QUERY'
    }
});
bestPictures.initialize();
$('input[name="search"]').typeahead(null, {
    name: 'best-pictures',
    displayKey: 'name',
    limit: 1000,
    minLength: 3,
    highlight: true,
    source: bestPictures.ttAdapter(),
    templates: {
        suggestion: Handlebars.compile('<p><small>{{type}}</small><br><a href="{{url}}"><strong>{{name}}</strong> – {{what}}</a></p>')
    }
});
JS, null, true);
    }
}
