<?php

/**
 * Page accounts/roles.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Roles;
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


// Build filter card
$filters    = FilterForm::new();
$defintions = $filters->getDefinitionsObject();
$defintions->get('roles_id')->setRender(false);
$defintions->get('rights_id')->setSize(6);
$defintions->get('status')->setSize(6);

$filters_card = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Users filters')
               ->setContent($filters);


// Build "roles" card
$roles_card = Card::new()
                  ->setTitle('Active roles')
                  ->setSwitches('reload')
                  ->setContent(Roles::new()
                                    ->setFilterFormObject($filters)
                                    ->getHtmlDataTableObject()
                                    ->setRowUrl('/accounts/role+:ROW.html'))
                  ->useForm(true)
                  ->setButtons(Buttons::new()
                                      ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/role.html')
                                      ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true));


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                                   <a href="' . Url::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Render and return the page grid
$grid = Grid::new()
            ->addGridColumn($filters_card->render() . $roles_card->render(), EnumDisplaySize::nine)
            ->addGridColumn($relevant_card->render() . $documentation_card->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Roles'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Roles'),
                                                       ]));
