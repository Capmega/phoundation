<?php

/**
 * Page security/incident
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->select('date_range')->isOptional()->isDateRange()
                   ->validate();


// Build the page content
$incident = Incident::load($get['id']);
$form     = $incident->getHtmlDataEntryFormObject();
$card     = Card::new()
                ->setTitle($incident->getTitle())
                ->setMaximizeSwitch(true)
                ->setContent($form)
                ->setButtons(Buttons::new()->addButton(
                    tr('Back'), EnumDisplayMode::secondary,
                    Url::getPrevious('/security/incidents.html')->addQueries(
                        $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
                    ),
                    true));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                              <a href="' . Url::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('This page shows the details of a single specific incident. The information on this page cannot be modified');


// Set page meta data
$url = Url::getWww('/security/incidents.html')->addQueries(
    $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
)->getSource();

Response::setHeaderTitle(tr('Incident'));
Response::setHeaderSubTitle($incident->getId());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    $url             => tr('Incidents management'),
    ''               => $incident->getId(),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($card, EnumDisplaySize::nine)
           ->addGridColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);
