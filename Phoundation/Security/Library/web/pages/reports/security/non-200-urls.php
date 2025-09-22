<?php

/**
 * Page security/non-200-urls
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Non200Urls\FilterForm;
use Phoundation\Web\Non200Urls\Non200Urls;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters      = FilterForm::new();
$o_filters_card = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Non200Urls filters')
               ->setContent($filters)
               ->useForm(true);


// Get a list of the URL's
$urls = Non200Urls::new()->load();


// Build "urls" table
$urls_card = Card::new()
                 ->setTitle(tr('Non-200 URL\'s (:count)', [':count' => $urls->getCount()]))
                 ->setSwitches('reload')
                 ->setContent($urls->getHtmlDataTableObject()
                     ->setRowUrl('/security/non-200-url+:ROW.html'))
                 ->useForm(true);

$urls_card->getForm()
          ->setAction(Url::newCurrent())
          ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new(Url::new('/security/authentications.html')->makeWww()->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''), tr('Authentications management')) .
                                  AnchorBlock::new(Url::new('/reports/security/incidents.html')->makeWww()->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''), tr('Incidents management')) .
                                  hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                     AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                     AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Non HTTP-200 URL\'s'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/security.html', tr('Security')),
    Breadcrumb::new(''              , tr('Non HTTP-200 URL\'s')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card . $urls_card              , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
