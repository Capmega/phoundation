<?php

use Phoundation\Accounts\Users\Users;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\WebPage;



// Build the page content
$table = Users::new()
    ->getHtmlTable()
    ->setRowUrl('/admin/accounts/:ROW.html');

echo Card::new()
    ->setContent($table->render())
    ->render();



// Set page meta data
WebPage::setHeaderTitle(tr('Users'));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
