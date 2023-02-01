<?php

use Phoundation\Developer\Libraries\Libraries;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Page;


// Build the page content
echo Card::new()
    ->setContent(Libraries::getHtmlDataTable()->render())
    ->render();



// Set page meta data
Page::setHeaderTitle(tr('Libraries'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'            => tr('Home'),
    '/phoundation' => tr('Phoundation'),
    ''             => tr('Libraries')
]));
