<?php

declare(strict_types=1);


use Phoundation\Accounts\Users\SignIns;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


$signins = SignIns::new();
$table   = $signins->getHtmlDataTable()->setCheckboxSelectors(EnumTableIdColumn::hidden);
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
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . UrlBuilder::getWww('/my/settings.html') . '">' . tr('Your settings') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/my/api-access.html') . '">' . tr('Your API access') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/my/profile.html') . '">' . tr('Your profile') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($signins->render(), EnumDisplaySize::nine)
            ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Your signin history'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                => tr('Home'),
                                                           '/my/profile.html' => tr('Profile'),
                                                           ''                 => tr('Your sign in history'),
                                                       ]));
