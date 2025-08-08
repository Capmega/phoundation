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
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Forms\FilterForm;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// Build the "filters" card
$o_filters = FilterForm::new()->setFilterSpecialUsers(false);
$o_filters->getDefinitionsObject()->setDefinitionRender('date_range', false)
                                  ->setDefinitionRender('status'    , false)
                                  ->setDefinitionSize('users_id'    , 12);


// Get the session object
$o_user     = $o_filters->getUserObject();
$o_sessions = $o_user?->getActiveSessions() ?? Iterator::new();

$o_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Filters')
                      ->setContent($o_filters);


// Get the "sessions" list and apply filters
$o_sessions_card = Card::new()
                       ->setTitle(tr('Active sessions (:count)', [':count' => $o_sessions->getCount()]))
                       ->setSwitches('reload')
                       ->setContent($o_sessions->getHtmlDataTableObject([
                                                   'id'     => tr('Identifier'),
                                                   'domain' => tr('Domain'),
                                                   'ip'     => tr('IP address'),
                                                   'start'  => tr('Start'),
                                               ])
                                               ->setRowUrl('/accounts/session+:ROW.html'));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(($o_user ? AnchorBlock::new('/accounts/user+' . $o_user->getId() . '.html', tr('Manage user :user', [':user' => $o_user->getDisplayName()])) : null) .
                                               AnchorBlock::new('/accounts/users.html'                        , tr('Manage users') , $o_user ? '<hr>' : null) .
                                               AnchorBlock::new('/accounts/roles.html'                        , tr('Manage roles')) .
                                               AnchorBlock::new('/accounts/rights.html'                       , tr('Manage rights')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


if ($o_user) {
// Set page meta data
    Response::setHeaderTitle(tr('Sessions for user'));
    Response::setHeaderSubTitle($o_user->getDisplayName());
    Response::setBreadcrumbs([
        Anchor::new('/'                          , tr('Home')),
        Anchor::new('/accounts/users.html'       , tr('Users')),
        Anchor::new('/accounts/session+:ROW.html', $o_user->getDisplayName()),
        Anchor::new(''                           , tr('Sessions')),
    ]);

} else {
    // Set page meta data
    Response::setHeaderTitle(tr('User sessions'));
    Response::setBreadcrumbs([
        Anchor::new('/'                   , tr('Home')),
        Anchor::new('/accounts.html'      , tr('Accounts')),
        Anchor::new('/accounts/users.html', tr('Users')),
        Anchor::new(''                    , tr('Sessions')),
    ]);
}


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card  . $o_sessions_card     , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
