<?php

/**
 * Page my/authentication-history
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Authentication;
use Phoundation\Accounts\Users\Authentications;
use Phoundation\Accounts\Users\AuthenticationsFilterForm;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters = AuthenticationsFilterForm::new();
$filters->getDefinitionsObject()->setDefinitionRender('users_id', false)
                                ->setDefinitionSize('date_range', 4)
                                ->setDefinitionSize('status'    , 4)
                                ->setDefinitionSize('action'    , 4);

$filters_card = Card::new()
                    ->setTitle('Authentication history')
                    ->setContent($filters);


// Build the authentication table
$authentications = Authentications::new()->setFilterFormObject($filters);
$authentications->getQueryBuilder()->addJoin('LEFT JOIN `accounts_users` ON `accounts_authentications`.`created_by` = `accounts_users`.`id`')
                                   ->addSelect('`accounts_authentications`.`id`')
                                   ->addSelect('`accounts_authentications`.`created_on`')
                                   ->addSelect('IFNULL(`accounts_authentications`.`status`, "Ok") AS `status`')
                                   ->addSelect('`accounts_authentications`.`ip_address`')
                                   ->addSelect('`accounts_authentications`.`account`')
                                   ->addSelect('`accounts_authentications`.`action`')
                                   ->addSelect('`accounts_authentications`.`method`')
                                   ->addWhere('`accounts_authentications`.`created_by` = :user_id', [
                                       ':user_id' => Session::getUserObject()->getId()
                                   ]);


// Build the "authentications" card
$authentications_card = Card::new()
                            ->setContent($authentications->getHtmlDataTableObject([
                                'id'         => tr('ID'),
                                'created_on' => tr('Date'),
                                'account'    => tr('Account'),
                                'ip_address' => tr('IP Address'),
                                'action'     => tr('Action'),
                                'status'     => tr('Status'),
                            ])
                            ->setRowUrl('/security/authentication+:ROW.html')
                            ->addRowCallback(function (IteratorInterface|array &$row, EnumTableRowType $type, &$params) {
                                // Adjust date to correct timezone and format
                                $row['created_on'] = PhoDateTime::new($row['created_on'], 'user')->format('human_datetime');
                                $row['status']     = Authentication::getHumanReadableStatus($row['status']);
                            }))
                            ->useForm(true);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::new('/my/profile.html')->makeWww() . '">' . tr('Manage my profile') . '</a><br>
                                   <a href="' . Url::new('/my/settings.html')->makeWww() . '">' . tr('Manage my settings') . '</a><br>
                                   <a href="' . Url::new('/my/password.html')->makeWww() . '">' . tr('Change my password') . '</a><br>                                 
                                   <a href="' . Url::new('/mfa/create.html')->makeWww()->addRedirect(Url::newCurrent()) . '">' . tr('Setup multi factor authentication') . '</a><br>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Authentications management'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'        => tr('Home'),
    '/my.html' => tr('My'),
    ''         => tr('Authentications history'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card  . '<br>' . $authentications_card, EnumDisplaySize::nine)
           ->addGridColumn($relevant_card . '<br>' . $documentation_card  , EnumDisplaySize::three);
