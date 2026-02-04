<?php

/**
 * Page /reports/security/incidents
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Os\Tasks\Task;
use Phoundation\Security\Incidents\FilterForm;
use Phoundation\Security\Incidents\Incidents;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters        = FilterForm::new();
$o_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Incidents filters')
                      ->setContent($filters);


// Build the incident table
$o_incidents = Incidents::new()->setFilterFormObject($filters);
$o_incidents->getQueryBuilderObject()->addSelect('`security_incidents`.`id`')
                                     ->addSelect('`security_incidents`.`type`')
                                     ->addSelect('`security_incidents`.`created_on`')
                                     ->addSelect('`security_incidents`.`severity`')
                                     ->addSelect('`security_incidents`.`title`')
                                     ->addSelect('COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `first_names`, `last_names`)), ""), `nickname`, `username`, `email`, "' . tr('System') . '") AS `user`')
                                     ->addJoin('JOIN `accounts_users` ON `accounts_users`.`id` = `security_incidents`.`created_by`')
                                     ->addWhere(QueryBuilder::is('`security_incidents`.`status`', null, ':status'));
$o_incidents->load();


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    switch (PostValidator::new()->getSubmitButton()) {
        case tr('Clear incidents'):

            // check if tasks exists and is running

            Task::new()
                ->setCommand('./pho')
                ->setArguments([
                    'security',
                    'incidents',
                    'clear'
                ])
                ->setName('clearing incidents')
                ->save();

            $success = (tr(':count incidents are being cleared', [
                ':count' => $o_incidents->getCount()
            ]));

            if ($filters->getDateRange()) {
                $success .= tr(' for date range :date_range', [
                    ':date_range' => $filters->getDateRange()
                ]);
            }

            if ($filters->getSeverities()) {
                $success .= tr(' with severity ":severity"', [
                    ':severity' => Strings::force($filters->getSeverities(), '"/"')
                ]);
            }

            if ($filters->getUsersId()) {
                $success .= tr(' for user ":user"', [
                    ':user' => User::new()->load($filters->getUsersId())->getDisplayName()
                ]);
            }

            Response::getFlashMessagesObject()->addSuccess($success);
            Response::redirect();

            default:
                throw new ValidationFailedException(tr('Unknown submit button ":button" specified', [
                    ':button' => PostValidator::new()->getSubmitButton()
                ]));
        }
}


// Build the "incidents" card
$o_incidents_card = Card::new()
                        ->setTitle(tr('Security incidents (:count)', [':count' => $o_incidents->getCount()]))
                        ->setSwitches('reload')
                        ->setContent($o_incidents->getHtmlDataTableObject([
                            'id'         => tr('Id'),
                            'user'       => tr('User'),
                            'type'       => tr('Type'),
                            'created_on' => tr('Created'),
                            'severity'   => tr('Severity'),
                            'title'      => tr('Title'),
                        ])
                        ->setRowUrls(Url::new('/reports/security/incident+:ROW.html')
                                        ->makeWww()
                                        ->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : '')))
                        ->useForm(true);


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/reports/security/authentications.html')->makeWww()->addQueries($filters->getUsersId()   ? 'users_id=' . $filters->getUsersId()  : '')->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''), tr('Authentications management')) .
                                    hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                       AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                       AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('This page displays all registered security incidents. All incidents, small or big (For example: user typed wrong password), are registered as security incidents, and are visible in this page.');


// Set page meta-data
Response::setHeaderTitle(tr('Incidents management'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/security.html', tr('Security')),
    Breadcrumb::new(''              , tr('Incidents management')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card  . $o_incidents_card    , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
