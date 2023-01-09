<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;



// Validate
GetValidator::new()
    ->select('id')->isId()
    ->validate();



// Build the page content
$user = User::get($_GET['id'])->getHtmlForm();

echo Card::new()
    ->setContent($user->render())
    ->render();



// Set page meta data
WebPage::setHeaderTitle(tr('User'));
WebPage::setHeaderSubTitle($user->getName());
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
