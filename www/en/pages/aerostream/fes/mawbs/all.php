<?php

use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\GridRow;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Plugins\Aerostream\AerostreamFes\Mawbs\FilterForm;
use Plugins\Aerostream\AerostreamFes\Hawbs\Hawbs;


// Build the page content


// Build users filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle('Filters')
    ->setContent($filters_content->render())
    ->useForm(true);


// Build users table
$table = Hawbs::new()->getHtmlDataTable()
    ->setRowUrl('/aerostream/fes/hawbs/hawb-:ROW.html');

$hawbs = Card::new()
    ->setTitle('Active Master Airway Bills')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true);

$hawbs->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setHasCollapseSwitch(true)
    ->setContent('<a href="' . UrlBuilder::getWww('/business/customers.html') . '">' . tr('Customers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/providers.html') . '">' . tr('Providers management') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the grid
$row1 = GridRow::new()
    ->addColumn($filters->render(), 9)
    ->addColumn($relevant->render(), 3);

$row2 = GridRow::new()->addColumn($hawbs);

$grid = Grid::new()
    ->addRow($row1)
    ->addRow($row2);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('FES - Master Airway Bills'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Master Airway Bills')
]));







//<div class="container-fluid">
//    <form action="http://aerostream.phoundation.org.local/en/aerostream/fes/mawbs/all.html" method="post" autocomplete="on" accept-charset="utf-8">
//        <div class="row">
//            <form action="http://aerostream.phoundation.org.local/en/aerostream/fes/mawbs/all.html" method="post" autocomplete="on" accept-charset="utf-8">
//                <div class="col-md-9">
//                    #
//                </div>
//                <div class="col-md-3">
//                    *
//                </div>
//            </form>
//        </div>
//        <div class="row">
//            <form action="http://aerostream.phoundation.org.local/en/aerostream/fes/mawbs/all.html" method="post" autocomplete="on" accept-charset="utf-8">
//                <div class="col-md-12">
//                    $
//                </div>
//            </form>
//        </div>
//    </form>
//</div>