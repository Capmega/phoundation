<?php

use Phoundation\Security\Incidents\FilterForm;
use Phoundation\Security\Incidents\Incidents;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Build the page content



// Build users filter card
$filters_content = FilterForm::new();


$filters = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle('Incidents filters')
    ->setContent($filters_content->render())
    ->useForm(true);



// Build users table
$table = Incidents::new()->getHtmlTable()
    ->setRowUrl('/security/incident-:ROW.html');

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
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($filters->render() . $users->render(), 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();



// Set page meta data
Page::setHeaderTitle(tr('Incidents'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Incidents')
]));
