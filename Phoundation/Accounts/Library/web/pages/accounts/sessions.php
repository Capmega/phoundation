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
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Forms\FilterForm;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Build the "filters" card
$filters = FilterForm::new()->setFilterSpecialUsers(false);
$filters->getDefinitionsObject()->setDefinitionRender('date_range', false)
                                ->setDefinitionRender('status'    , false)
                                ->setDefinitionSize('users_id'    , 12);

$user     = $filters->getUserObject();
$sessions = $user?->getActiveSessions() ?? Iterator::new();

$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Filters')
                    ->setContent($filters);


// Get the "sessions" list and apply filters
$sessions_card = Card::new()
                  ->setTitle('Active sessions')
                  ->setSwitches('reload')
                  ->setContent($sessions->getHtmlDataTableObject([
                      'id'     => tr('Identifier'),
                      'domain' => tr('Domain'),
                      'ip'     => tr('IP address'),
                      'start'  => tr('Start'),
                  ])->setRowUrl('/accounts/session+:ROW.html'));


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(($user ? Anchor::new('/accounts/user+' . $user->getId() . '.html', tr('Manage user :user', [':user' => $user->getDisplayName()])) : null) .
                                           Anchor::new('/accounts/users.html'                      , tr('Manage users') , $user ? '<hr>' : null) .
                                           Anchor::new('/accounts/roles.html'                      , tr('Manage roles') , '<br>') .
                                           Anchor::new('/accounts/rights.html'                     , tr('Manage rights'), '<br>'));


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


if ($user) {
// Set page meta data
    Response::setHeaderTitle(tr('Sessions for user'));
    Response::setHeaderSubTitle($user->getDisplayName());
    Response::setBreadcrumbs([
        Anchor::new('/'                          , tr('Home')),
        Anchor::new('/accounts/users.html'       , tr('Users')),
        Anchor::new('/accounts/session+:ROW.html', $user->getDisplayName()),
        Anchor::new(''                           , tr('Sessions')),
    ]));

} else {
    // Set page meta data
    Response::setHeaderTitle(tr('User sessions'));
    Response::setBreadcrumbs([
        Anchor::new('/'                   , tr('Home')),
        Anchor::new('/accounts.html'      , tr('Accounts')),
        Anchor::new('/accounts/users.html', tr('Users')),
        Anchor::new(''                    , tr('Sessions')),
    ]));
}


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card  . $sessions_card     , EnumDisplaySize::nine)
           ->addGridColumn($relevant_card . $documentation_card, EnumDisplaySize::three);
