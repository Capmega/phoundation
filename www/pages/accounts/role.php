<?php

declare(strict_types=1);


use Phoundation\Accounts\Roles\Role;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;


// Validate
$get = GetValidator::new()
    ->select('id')->isDbId()
    ->validate();


// Build the page content
$role = Role::get($get['id']);
$form = $role->getHtmlForm();
$card = Card::new()
    ->setTitle(tr('Edit data for role :name', [':name' => $role->getName()]))
    ->setContent($form->render());


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
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
    ->addColumn($card, DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three)
    ->addRow($rights, DisplaySize::nine);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Role'));
Page::setHeaderSubTitle($role->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/accounts/roles.html' => tr('Roles'),
    ''                     => $role->getName()
]));
