<?php

declare(strict_types=1);


use Phoundation\Business\Providers\FilterForm;
use Phoundation\Business\Providers\Providers;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


// Build the page content


// Build providers filter card
$filters_content = FilterForm::new();

$filters = Card::new()
               ->setTitle('Providers filters')
               ->setCollapseSwitch(true)
               ->setContent($filters_content->render())
               ->useForm(true);


// Build providers table
$table = Providers::new()->getHtmlDataTable()
                  ->setRowUrl('/business/provider+:ROW.html');

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
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setCollapseSwitch(true)
                ->setContent('<a href="' . UrlBuilder::getWww('/business/customers.html') . '">' . tr('Customers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/companies.html') . '">' . tr('Companies management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setCollapseSwitch(true)
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($filters->render() . $providers->render(), EnumDisplaySize::nine)
            ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Providers'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Providers'),
                                                       ]));
