<?php

declare(strict_types=1);


use Phoundation\Security\Incidents\FilterForm;
use Phoundation\Security\Incidents\Incidents;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


// Build the page content


// Build users filter card
$filters_content = FilterForm::new();

$filters = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Incidents filters')
               ->setContent($filters_content->render())
               ->useForm(true);


// Build users table
$table = Incidents::new()->getHtmlDataTable()
                  ->setRowUrl('/security/incident+:ROW.html');

$users = Card::new()
             ->setTitle('Security incidents')
             ->setSwitches('reload')
             ->setContent($table->render())
             ->useForm(true);

$users->getForm()
      ->setAction(UrlBuilder::getCurrent())
      ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($filters->render() . $users->render(), DisplaySize::nine)
            ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Incidents'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Incidents'),
                                                       ]));
