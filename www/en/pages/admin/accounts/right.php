<?php

use Phoundation\Accounts\Rights\Right;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;



// Check access
Page::hasRightsOrRedirects('admin');



// Validate
GetValidator::new()
    ->select('id')->isId()
    ->validate();



// Build the page content
$right = Right::get($_GET['id']);
$form  = Right::get($_GET['id'])->getHtmlForm();
$card  = Card::new()
    ->setTitle(tr('Edit data for right :name', [':name' => $right->getName()]))
    ->setContent($form->render());



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::www('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . UrlBuilder::www('/accounts/roles.html') . '">' . tr('Roles management') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($card, 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Right'));
Page::setHeaderSubTitle($right->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                     => tr('Home'),
    '/accounts/rights.html' => tr('Rights'),
    ''                      => $right->getName()
]));
