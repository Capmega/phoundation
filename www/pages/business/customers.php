<?php

declare(strict_types=1);


use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Customers\FilterForm;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Build the page content


// Build customers filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setTitle('Customers filters')
    ->setCollapseSwitch(true)
    ->setContent($filters_content->render())
    ->useForm(true);


// Build customers table
$table = Customers::new()->getHtmlDataTable()
    ->setRowUrl('/business/customer-:ROW.html');

$customers = Card::new()
    ->setTitle('Active customers')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true);

$customers->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setCollapseSwitch(true)
    ->setContent('<a href="' . UrlBuilder::getWww('/business/providers.html') . '">' . tr('Providers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/companies.html') . '">' . tr('Companies management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setCollapseSwitch(true)
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters->render() . $customers->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Customers'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Customers')
]));
