<?php

/**
 * Page security/incident
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
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
$_incident = Incident::new()->load($get['id']);
$_form     = $_incident->getHtmlDataEntryFormObject();
$_card     = Card::new()
                  ->setTitle(tr('Incident data'))
                  ->setMaximizeSwitch(true)
                  ->setContent($_form)
                  ->setButtonsObject(Buttons::new()->addButton(
                      tr('Back'), EnumDisplayMode::secondary,
                      Url::newPrevious('/reports/security/incidents.html')->addQueries(
                          $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
                      ),
                      true));


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/reports/security/authentications.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Authentications management')) .
                                    AnchorBlock::new(Url::new('/reports/security/incidents.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Incidents management')) .
                                    hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                       AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                       AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('This page shows the details of a single specific incident. The information on this page cannot be modified');


// Set page meta-data
$_url = Url::new('/reports/security/incidents.html')->makeWww()->addQueries(
    $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
);

Response::setHeaderTitle(tr('Incident'));
Response::setHeaderSubTitle($_incident->getDisplayId());
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/security.html', tr('Security')),
    Breadcrumb::new($_url          , tr('Incidents management')),
    Breadcrumb::new(''              , $_incident->getDisplayId()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($_card                                 , EnumDisplaySize::nine)
           ->addGridColumn($_relevant_card . $_documentation_card, EnumDisplaySize::three);
