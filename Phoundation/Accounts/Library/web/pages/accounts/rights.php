<?php

/**
 * Page accounts/rights.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\FilterForm;
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


// Build users filter card
$filters_content = FilterForm::new();

$filters = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Users filters')
               ->setContent($filters_content->render())
               ->useForm(true);


// Build users table
$buttons = Buttons::new()
                  ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/right.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);


// Build rights table
$table = Rights::new()
               ->getHtmlDataTable()
               ->setRowUrl('/accounts/right+:ROW.html');

$rights = Card::new()
              ->setTitle('Active rights')
              ->setSwitches('reload')
              ->setContent($table->render())
              ->useForm(true)
              ->setButtons($buttons);

$rights->getForm()
       ->setAction(Url::getCurrent())
       ->setMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . Url::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($filters->render() . $rights, EnumDisplaySize::nine)
            ->addColumn($relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Rights'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Rights'),
                                                       ]));
