<?php

/**
 * Page accounts/sessions.php
 *
 * This page will display all the active sessions for the specified session
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Forms\FilterForm;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// Build the "filters" card
$_filters = FilterForm::new()->setFilterSpecialUsers(false);
$_filters->getDefinitionsObject()->setDefinitionRender('date_range', false)
                                  ->setDefinitionRender('status'    , false)
                                  ->setDefinitionSize('users_id'    , 12);


// Get the session object
$_user     = $_filters->getUserObject();
$_sessions = $_user?->getActiveSessions() ?? Iterator::new();

$_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Filters')
                      ->setContent($_filters);


// Get the "sessions" list and apply filters
$_sessions_card = Card::new()
                       ->setTitle(tr('Active sessions (:count)', [':count' => $_sessions->getCount()]))
                       ->setSwitches('reload')
                       ->setContent($_sessions->getHtmlDataTableObject([
                                                   'id'     => tr('Identifier'),
                                                   'domain' => tr('Domain'),
                                                   'ip'     => tr('IP address'),
                                                   'start'  => tr('Start'),
                                               ])
                                               ->setRowUrls('/accounts/session+:ROW.html'));


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(($_user ? AnchorBlock::new('/accounts/user+' . $_user->getId() . '.html', tr('Manage user :user', [':user' => $_user->getDisplayName()])) : null) .
                                               AnchorBlock::new('/accounts/users.html'                        , tr('Manage users') , $_user ? '<hr>' : null) .
                                               AnchorBlock::new('/accounts/roles.html'                        , tr('Manage roles')) .
                                               AnchorBlock::new('/accounts/rights.html'                       , tr('Manage rights')));


// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


if ($_user) {
// Set page meta-data
    Response::setHeaderTitle(tr('Sessions for user'));
    Response::setHeaderSubTitle($_user->getDisplayName());
    Response::setBreadcrumbs([
        Breadcrumb::new('/'                          , tr('Home')),
        Breadcrumb::new('/accounts/users.html'       , tr('Users')),
        Breadcrumb::new('/accounts/session+:ROW.html', $_user->getDisplayName()),
        Breadcrumb::new(''                           , tr('Sessions')),
    ]);

} else {
    // Set page meta-data
    Response::setHeaderTitle(tr('User sessions'));
    Response::setBreadcrumbs([
        Breadcrumb::new('/'                   , tr('Home')),
        Breadcrumb::new('/accounts.html'      , tr('Accounts')),
        Breadcrumb::new('/accounts/users.html', tr('Users')),
        Breadcrumb::new(''                    , tr('Sessions')),
    ]);
}


// Render and return the page grid
return Grid::new()
           ->addGridColumn($_filters_card . $_sessions_card     , EnumDisplaySize::nine)
           ->addGridColumn($_relevant_card . $_documentation_card, EnumDisplaySize::three);
