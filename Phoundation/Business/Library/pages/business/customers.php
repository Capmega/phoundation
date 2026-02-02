<?php

/**
 * Page customers
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Phoundation\Business
 */


declare(strict_types=1);

use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Customers\FilterForm;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Build the page content


// Build customers filter card
$filters      = FilterForm::new();
$o_filters_card = Card::new()
               ->setTitle('Customers filters')
               ->setCollapseSwitch(true)
               ->setContent($filters->render())
               ->useForm(true);


// Build customers table
$customers_card = Card::new()
                      ->setTitle('Active customers')
                      ->setSwitches('reload')
                      ->useForm(true)
                      ->setContent(Customers::new()->getHtmlDataTableObject()
                                                   ->setRowUrls('/business/customer+:ROW.html'));

// TODO Is this necessary? Default form action should be current and default method should be POST already
$customers_card->getForm()
               ->setAction(Url::newCurrent())
               ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setCollapseSwitch(true)
                     ->setContent(AnchorBlock::new(Url::new('/business/providers.html')->makeWww(), tr('Providers management')) .
                                  AnchorBlock::new(Url::new('/business/companies.html')->makeWww(), tr('Companies management')));


// Build documentation
$o_documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setCollapseSwitch(true)
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta-data
Response::setHeaderTitle(tr('Customers'));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Customers')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card . $customers_card    , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
