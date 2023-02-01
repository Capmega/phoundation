<?php

use Phoundation\Accounts\Users\FilterForm;
use Phoundation\Accounts\Users\Users;
use Phoundation\Core\Meta\MetaList;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Validate GET
GetValidator::new()
    ->select('id')->sanitizeForceArray('-')->each()->isId()
    ->validate();



$meta  = MetaList::new($_GET['id']);
$table = $meta->getHtmlDataTable();
$card  = Card::new()
    ->setTitle('Registered activities')
    ->setSwitches('reload')
    ->setContent($table->render());



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($card->render(), 9)
    ->addColumn($documentation->render(), 3);

echo $grid->render();



// Set page meta data
Page::setHeaderTitle(tr('Audit information'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Audit information')
]));
