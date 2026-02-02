<?php

/**
 * Page reports/os/tasks
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);


use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Os\Tasks\Tasks;
use Phoundation\Os\Tasks\FilterForm;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters        = FilterForm::new();
$o_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Tasks filters')
                      ->setContent($filters);


// Build the incident table
$o_tasks = Tasks::new()->setFilterFormObject($filters);
$o_tasks->getQueryBuilderObject()->addSelect('`os_tasks`.`id`')
                                 ->addSelect('`os_tasks`.`name`')
                                 ->addSelect('`os_tasks`.`created_on`')
                                 ->addSelect('`os_tasks`.`start`')
                                 ->addSelect('`os_tasks`.`stop`')
                                 ->addSelect('`os_tasks`.`spent`')
                                 ->addSelect('`os_tasks`.`status`')
                                 ->addSelect('`os_tasks`.`command`')
                                 ->addSelect('`os_tasks`.`arguments`')
                                 ->addSelect('CONCAT(`os_tasks`.`command`, " ", LEFT(RIGHT(`os_tasks`.`arguments`, LENGTH(`os_tasks`.`arguments`) - 7), LENGTH(`os_tasks`.`arguments`) - 10)) AS `full_command`');
$o_tasks->load();


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    $submit_button = PostValidator::new()->getSubmitButton(prefix: true);
    if ($submit_button) {
        $message = 'test';
        Response::getFlashMessagesObject()
                ->addSuccess($message);
        Response::redirect();

    } else {
        throw new ValidationFailedException(tr('Unknown submit button ":button" specified', [
            ':button' => PostValidator::new()->getSubmitButton()
        ]));
    }
}


// Build the "tasks" card
$o_tasks_card = Card::new()
                    ->setTitle(tr('Tasks (:count)', [
                        ':count' => $o_tasks->getCount()
                    ]))
                    ->setSwitches('reload')
                    ->setContent($o_tasks->getHtmlDataTableObject([
                                            'id'           => tr('Id'),
                                            'name'         => tr('Name'),
                                            'status'       => tr('Status'),
                                            'created_on'   => tr('Created On'),
                                            'start'        => tr('Start Time'),
                                            'stop'         => tr('Stop Time'),
                                            'spent'        => tr('Spent'),
                                            'full_command' => tr('Command'),
                                         ])
                                         ->setRowUrls(Url::new('reports/os/tasks+:ROW.html')->makeWww()->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''))
                                         ->addRowCallback(function (array &$row, EnumTableRowType $type, &$params) use ($o_tasks) {
                                             $row['date_range'] = $o_tasks->get($row['id'])->getFullCommand();
                                         }))
                    ->useForm(true);


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/reports/security/authentications.html')
                                                        ->makeWww()
                                                        ->addQueries($filters->getUsersId() ? 'users_id=' . $filters->getUsersId() : '')
                                                        ->addQueries($filters->getDateRange() ? 'date_range=' . $filters->getDateRange() : ''), tr('Authentications management')) .
                                    hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                       AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                       AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('This page displays all tasks.');


// Set page meta-data
Response::setHeaderTitle(tr('Tasks management'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'       , tr('Home')),
    Breadcrumb::new('/os.html', tr('Os')),
    Breadcrumb::new(''        , tr('Tasks management')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card  . $o_tasks_card    , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
