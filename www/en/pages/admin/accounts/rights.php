<?php

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;



// Build the page content



// Build rights table
$table = Rights::new()->getHtmlTable()
    ->setRowUrl('/admin/accounts/right-:ROW.html');

$rights = Card::new()
    ->setTitle('Active rights')
    ->setButtons('reload')
    ->setContent($table->render())
    ->useForm(true);

$rights->getForm()
        ->setAction(Url::build()->www())
        ->setMethod('POST');



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . Url::build('/admin/accounts/users.html')->www() . '">' . tr('Users management') . '</a><br>
                         <a href="' . Url::build('/admin/accounts/roles.html')->www() . '">' . tr('Roles management') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($rights, 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();



// Set page meta data
WebPage::setHeaderTitle(tr('Rights'));
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Rights')
]));
