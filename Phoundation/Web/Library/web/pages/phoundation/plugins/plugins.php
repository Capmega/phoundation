<?php

/**
 * Page phoundation/plugins/plugins.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


//// Build plugins filter card
//$filters      = FilterForm::new();
//$filters_card = Card::new()
//                    ->setCollapseSwitch(true)
//                    ->setTitle('Plugins filters')
//                    ->setContent($filters)
//                    ->useForm(true);


// Build "plugins" table
$buttons = Buttons::new()
                  ->addButton(tr('Add'), EnumDisplayMode::primary, '/phoundation/plugins/plugin.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::danger, EnumButtonType::submit, true, true)
                  ->addButton(tr('Disable'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);


// Build "plugins" card
$plugins_card = Card::new()
               ->setTitle('Active plugins')
               ->setSwitches('reload')
               ->setContent(Plugins::new()
                                   ->getHtmlDataTableObject()
                                   ->setRowUrl('/phoundation/plugins/plugin+:ROW.html'))
               ->useForm(true)
               ->setButtons($buttons);

$plugins_card->getForm()
             ->setAction(Url::newCurrent())
             ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')));


// Build documentation
$o_documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Plugins management'));
Response::setBreadcrumbs([
    Anchor::new('/'                , tr('Home')),
    Anchor::new('/phoundation.html', tr('Phoundation')),
    Anchor::new(''                 , tr('Plugins')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($plugins_card                       , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
