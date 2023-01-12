<?php

use Phoundation\Accounts\Roles\Role;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;



// Validate
GetValidator::new()
    ->select('id')->isId()
    ->validate();



// Build the page content
$role = Role::get($_GET['id']);
$form = Role::get($_GET['id'])->getHtmlForm();
$card = Card::new()
    ->setTitle(tr('Edit data for role :name', [':name' => $role->getName()]))
    ->setContent($form->render());



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . Url::build('/admin/accounts/users.html')->www() . '">' . tr('Users management') . '</a><br>
                         <a href="' . Url::build('/admin/accounts/rights.html')->www() . '">' . tr('Rights management') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build the rights list management section
$rights = Card::new()
    ->setTitle(tr('Rights for this role'))
    ->setContent($role->getRightsHtmlForm()
        ->setAction('#')
        ->setMethod('POST')
        ->render());



// Build and render the grid
$grid = Grid::new()
    ->addColumn($card, 9)
    ->addColumn($relevant->render() . $documentation->render(), 3)
    ->addRow($rights, 9);


echo $grid->render();


// Set page meta data
WebPage::setHeaderTitle(tr('Role'));
WebPage::setHeaderSubTitle($role->getName());
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/admin/'                    => tr('Home'),
    '/admin/accounts/roles.html' => tr('Roles'),
    ''                           => $role->getName()
]));
