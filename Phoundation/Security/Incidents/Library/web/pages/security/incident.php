<?php

declare(strict_types=1);


use Phoundation\Data\Validator\GetValidator;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->validate();


// Build the page content
$incident = Incident::load($get['id']);
$form     = $incident->getHtmlDataEntryFormObject();
$card     = Card::new()
                ->setTitle($incident->getTitle())
                ->setMaximizeSwitch(true)
                ->setContent($form->render())
                ->setButtons(Buttons::new()->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/security/incidents.html'), true));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . UrlBuilder::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($card, EnumDisplaySize::nine)
            ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Incident'));
Response::setHeaderSubTitle($incident->getId());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                        => tr('Home'),
                                                           '/security/incidents.html' => tr('Incidents'),
                                                           ''                         => $incident->getId(),
                                                       ]));
