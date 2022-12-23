<?php

use Phoundation\Libraries\Libraries;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\WebPage;



// Build the page content
echo Card::new()
    ->setContent(Libraries::getHtmlTable()->render())
    ->render();



// Set page meta data
WebPage::setHeaderTitle(tr('Libraries'));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/'            => tr('Home'),
    '/phoundation' => tr('Phoundation'),
    ''             => tr('Libraries')
]));
