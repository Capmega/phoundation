<?php

/**
 * Page security/authentications
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Authentication;
use Phoundation\Accounts\Users\Authentications;
use Phoundation\Accounts\Users\AuthenticationsFilterForm;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Date\DateTime;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters      = AuthenticationsFilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Authentication filters')
                    ->setContent($filters);


// Build the authentication table
$authentications = Authentications::new()->setFilterFormObject($filters);
$builder         = $authentications->getQueryBuilder()->addJoin('LEFT JOIN `accounts_users` ON `accounts_authentications`.`created_by` = `accounts_users`.`id`')
                                                      ->addSelect('`accounts_authentications`.`id`')
                                                      ->addSelect('`accounts_authentications`.`created_on`')
                                                      ->addSelect('IFNULL(`accounts_authentications`.`status`, "Ok") AS `status`')
                                                      ->addSelect('COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `accounts_users`.`first_names`, `accounts_users`.`last_names`)), ""), `accounts_users`.`nickname`, `accounts_users`.`username`, `accounts_users`.`email`, "' . tr('No matched account') . '") AS `user`')
                                                      ->addSelect('`accounts_authentications`.`ip_address`')
                                                      ->addSelect('`accounts_authentications`.`account`')
                                                      ->addSelect('`accounts_authentications`.`action`')
                                                      ->addSelect('`accounts_authentications`.`method`');

// Build the "authentications" card
$authentications_card = Card::new()
                            ->setTitle('Authentications')
                            ->setSwitches('reload')
                            ->setContent($authentications->getHtmlDataTableObject([
                                                             'id'         => tr('ID'),
                                                             'created_on' => tr('Date'),
                                                             'account'    => tr('Account'),
                                                             'user'       => tr('User'),
                                                             'ip_address' => tr('IP Address'),
                                                             'action'     => tr('Action'),
                                                             'status'     => tr('Status'),
                                                         ])
                                                         ->setRowUrl(Url::getWww('/security/authentication+:ROW.html')->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''))
                                                         ->addRowCallback(function (IteratorInterface|array &$row, EnumTableRowType $type, &$params) {
                                                             // Adjust date to correct timezone and format
                                                             $row['created_on'] = DateTime::new($row['created_on'], 'user')->format('human_datetime');
                                                             $row['status']     = Authentication::getHumanReadableStatus($row['status']);
                                                         }))
                            ->useForm(true);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::getWww('/security/incidents.html')->addQueries($filters->getUsersId()   ? 'users_id='   . $filters->getUsersId()   : '')
                                                                                       ->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : '')  . '">' . tr('Incidents management') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Authentications management'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    ''               => tr('Authentications management'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card->render()  . $authentications_card->render(), EnumDisplaySize::nine)
           ->addGridColumn($relevant_card->render() . $documentation_card->render()  , EnumDisplaySize::three);
