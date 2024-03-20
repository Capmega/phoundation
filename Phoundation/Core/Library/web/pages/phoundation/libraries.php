<?php

declare(strict_types=1);


use Phoundation\Developer\Libraries\Libraries;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Requests\Response;

// Build the page content
echo Card::new()
    ->setContent(Libraries::getHtmlDataTable()->render())
    ->render();


// Set page meta data
Response::setHeaderTitle(tr('Libraries'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'            => tr('Home'),
    '/phoundation' => tr('Phoundation'),
    ''             => tr('Libraries')
]));
