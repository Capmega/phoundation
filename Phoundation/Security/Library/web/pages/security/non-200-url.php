<?php

/**
 * Page security/non-200-url
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
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Non200Urls\Non200Url;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->validate();


// Build the page content
$url  = Non200Url::new()->load($get['id']);
$form = $url->getHtmlDataEntryFormObject();
$o_card = Card::new()
            ->setTitle($url->getDisplayName())
            ->setMaximizeSwitch(true)
            ->setContent($form)
            ->setButtons(Buttons::new()->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/security/non-200-urls.html'), true));


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new(Url::new('/security/authentications.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Authentications management')) .
                                  AnchorBlock::new(Url::new('/security/incidents.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Incidents management')) .
                                  AnchorBlock::new(Url::new('/security/non-200-urls.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Non-200 URL\'s management')) .
                                  hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                     AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                     AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Non HTTP-200 URL'));
Response::setHeaderSubTitle($url->getDisplayId());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                          , tr('Home')),
    Breadcrumb::new('/security.html'             , tr('Security')),
    Breadcrumb::new('/security/non-200-urls.html', tr('Non HTTP-200 URL\'s')),
    Breadcrumb::new(''                           , $url->getDisplayId()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_card                                   , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
