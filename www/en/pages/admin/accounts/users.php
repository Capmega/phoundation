<?php

use Phoundation\Accounts\Users\Users;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;



// Build the page content



// Build users table
$table = Users::new()->getHtmlTable()
    ->setRowUrl('/admin/accounts/:ROW.html');

$users = Card::new()
    ->setTitle('')
    ->setButtons('reload')
    ->setContent($table->render())
    ->useForm(true);

$users->getForm()
        ->setAction(Url::build()->www())
        ->setMethod('POST');



// Build documentation
$documentation = Card::new()
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($users, 6)
    ->addColumn($documentation, 6);

echo $grid->render();



// Set page meta data
WebPage::setHeaderTitle(tr('Users'));
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
