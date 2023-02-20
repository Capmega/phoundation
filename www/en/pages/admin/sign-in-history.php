<?php

use Phoundation\Accounts\Users\FilterForm;
use Phoundation\Accounts\Users\SignIns;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



$signins = SignIns::new();



$table = $signins->getHtmlDataTable()->setCheckboxSelectors(false);

$signins = Card::new()
    ->setTitle('Your signin history')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true);

$signins->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/settings.html') . '">' . tr('Your settings') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/api-access.html') . '">' . tr('Your API access') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/profile.html') . '">' . tr('Your profile') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($signins->render(), 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();



// Set page meta data
Page::setHeaderTitle(tr('Your signin history'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'             => tr('Home'),
    '/profile.html' => tr('Profile'),
    ''              => tr('Your sign in history')
]));
