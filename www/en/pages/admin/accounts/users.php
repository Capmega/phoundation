<?php

use Phoundation\Accounts\Users\Users;
use Phoundation\Web\WebPage;
use Templates\AdminLte\Components\BreadCrumbs;
use Templates\AdminLte\Components\Widgets\Cards\Card;



// Build the page content
echo Card::new()
    ->setTitle('Users')
    ->setType('')
    ->setButtons()
    ->setContent(Users::new()->getHtmlTable()->render())
    ->render();
//echo Users::new()->htmlTable()->render();



// Set page meta data
WebPage::setHeaderTitle(tr('Users'));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
