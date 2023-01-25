<?php

use Phoundation\Developer\Incidents\FilterForm;
use Phoundation\Developer\Incidents\Incidents;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Build the page content



// Build incidents filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle('Incidents filters')
    ->setContent($filters_content->render())
    ->useForm(true);



// Build incidents table
$table = Incidents::new()->getHtmlTable()
    ->setRowUrl('/development/incident-:ROW.html');

$incidents = Card::new()
    ->setTitle('Open incidents')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true);

$incidents->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::www('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                         <a href="' . UrlBuilder::www('/security/security.html') . '">' . tr('Security management') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($filters->render() . $incidents->render(), 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();



// Set page meta data
Page::setHeaderTitle(tr('Development incidents'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Development incidents')
]));
