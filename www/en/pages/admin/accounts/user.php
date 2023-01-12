<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;



// Validate
GetValidator::new()
    ->select('id')->isId()
    ->validate();



// Build the page content
$user = User::get($_GET['id']);
$form = User::get($_GET['id'])->getHtmlForm();
$card = Card::new()
    ->setContent($form->render());



// Build documentation
$documentation = Card::new()
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($card, 6)
    ->addColumn($documentation, 6);

echo $grid->render();


// Set page meta data
WebPage::setHeaderTitle(tr('User'));
WebPage::setHeaderSubTitle($user->getName());
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/admin/'                    => tr('Home'),
    '/admin/accounts/users.html' => tr('Users'),
    ''                           => $user->getDisplayName()
]));
