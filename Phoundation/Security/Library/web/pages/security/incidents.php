<?php

/**
 * Page security/incidents
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Security\Incidents\FilterForm;
use Phoundation\Security\Incidents\Incidents;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters      = FilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Incidents filters')
                    ->setContent($filters);


// Build the incident table
$incidents = Incidents::new()->setFilterFormObject($filters);
$builder   = $incidents->getQueryBuilder()
                       ->addSelect('`security_incidents`.`id`')
                       ->addSelect('`security_incidents`.`type`')
                       ->addSelect('`security_incidents`.`created_on`')
                       ->addSelect('`security_incidents`.`severity`')
                       ->addSelect('`security_incidents`.`title`')
                       ->addSelect('COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS `user`')
                       ->addJoin('JOIN `accounts_users` ON `accounts_users`.`id` = `security_incidents`.`created_by`')
                       ->addWhere(SqlQueries::is('`security_incidents`.`status`', null, ':status'));


// Build the "incidents" card
$incidents_card = Card::new()
                      ->setTitle('Security incidents')
                      ->setSwitches('reload')
                      ->setContent($incidents->getHtmlDataTableObject([
                          'id'         => tr('Id'),
                          'user'       => tr('User'),
                          'type'       => tr('Type'),
                          'created_on' => tr('Created'),
                          'severity'   => tr('Severity'),
                          'title'      => tr('Title'),
                      ])
                      ->setRowUrl(Url::getWww('/security/incident+:ROW.html')
                                     ->addQueries(
                                         $filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''
                                     )))
                      ->useForm(true);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::getWww('/security/authentications.html')->addQueries($filters->getUsersId()   ? 'users_id='   . $filters->getUsersId()   : '')
                                                                                             ->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : '') . '">' . tr('Authentications management') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('This page displays all registered security incidents. All incidents, small or big (For example: user typed wrong password), are registered as security incidents, and are visible in this page.');


// Set page meta data
Response::setHeaderTitle(tr('Incidents management'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    ''               => tr('Incidents management'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card->render()  . $incidents_card->render()    , EnumDisplaySize::nine)
           ->addGridColumn($relevant_card->render() . $documentation_card->render(), EnumDisplaySize::three);
