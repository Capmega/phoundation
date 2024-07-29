<?php

declare(strict_types=1);

use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


/**
 * Page phoundation/plugins/plugins.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


// Build plugins filter card
$filters_content = FilterForm::new();

$filters = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Plugins filters')
               ->setContent($filters_content->render())
               ->useForm(true);


// Build plugins table
$buttons = Buttons::new()
                  ->addButton(tr('Add'), EnumDisplayMode::primary, '/phoundation/plugins/plugin.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::danger, EnumButtonType::submit, true, true)
                  ->addButton(tr('Disable'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);


// Build plugins table
$table = Plugins::new()
                ->getHtmlDataTable()
                ->setRowUrl('/phoundation/plugins/plugin+:ROW.html');

$plugins = Card::new()
               ->setTitle('Active plugins')
               ->setSwitches('reload')
               ->setContent($table->render())
               ->useForm(true)
               ->setButtons($buttons);

$plugins->getForm()
        ->setAction(Url::getCurrent())
        ->setMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($filters->render() . $plugins, EnumDisplaySize::nine)
            ->addColumn($relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Plugins management'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                 => tr('Home'),
                                                           '/phoundation.html' => tr('Phoundation'),
                                                           ''                  => tr('Plugins'),
                                                       ]));
