<?php

declare(strict_types=1);


use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Non200Urls\Non200Url;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
    ->select('id')->isDbId()
    ->validate();


// Build the page content
$url = Non200Url::get($get['id']);
$form     = $url->getHtmlDataEntryForm();
$card     = Card::new()
    ->setTitle($url->getTitle())
    ->setMaximizeSwitch(true)
    ->setContent($form->render())
    ->setButtons(Buttons::new()->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/security/non-200-urls.html'), true));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($card, DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Non HTTP-200 URL'));
Response::setHeaderSubTitle($url->getId());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                           => tr('Home'),
    '/security.html'              => tr('Security'),
    '/security/non-200-urls.html' => tr('Non HTTP-200 URL\'s'),
    ''                            => $url->getId()
]));
