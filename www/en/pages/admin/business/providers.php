<?php

use Phoundation\Business\Providers\FilterForm;
use Phoundation\Business\Providers\Providers;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Build the page content



// Build providers filter card
$filters_content = FilterForm::new();


$filters = Card::new()
    ->setTitle('Providers filters')
    ->setHasCollapseSwitch(true)
    ->setContent($filters_content->render())
    ->useForm(true);



// Build providers table
$table = Providers::new()->getHtmlTable()
    ->setRowUrl('/business/provider-:ROW.html');

$providers = Card::new()
    ->setTitle('Active providers')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true);

$providers->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setHasCollapseSwitch(true)
    ->setContent('<a href="' . UrlBuilder::getWww('/business/customers.html') . '">' . tr('Customers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/companies.html') . '">' . tr('Companies management') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setHasCollapseSwitch(true)
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($filters->render() . $providers->render(), 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();



// Set page meta data
Page::setHeaderTitle(tr('Providers'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Providers')
]));
