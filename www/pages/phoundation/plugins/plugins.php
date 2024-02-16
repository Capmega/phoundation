<?php

declare(strict_types=1);

use Phoundation\Core\Plugins\Plugins;
use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\ButtonType;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page phoundation/plugins/plugins.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
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
    ->addButton(tr('Add'), DisplayMode::primary, '/phoundation/plugins/plugin.html')
    ->addButton(tr('Delete'), DisplayMode::danger, ButtonType::submit, true, true)
    ->addButton(tr('Disable'), DisplayMode::warning, ButtonType::submit, true, true);


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
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters->render() . $plugins, DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Plugins management'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                 => tr('Home'),
    '/phoundation.html' => tr('Phoundation'),
    ''                  => tr('Plugins')
]));