<?php

/**
 * Page security/incidents
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Authentications;
use Phoundation\Accounts\Users\AuthenticationsFilterForm;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters      = AuthenticationsFilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Authentication filters')
                    ->setContent($filters->render());


// Build the incident table
$table = Authentications::new();
$query = $table->getQueryBuilder()
    ->addJoin('JOIN `accounts_users` ON `accounts_authentications`.`users_id` = `accounts_users`.`id`')
    ->addSelect('`accounts_authentications`.`id`')
    ->addSelect('COALESCE(NULLIF(TRIM(CONCAT_WS(" ", `accounts_users`.`first_names`, `accounts_users`.`last_names`)), ""), `accounts_users`.`nickname`, `accounts_users`.`username`, `accounts_users`.`email`, "' . tr('System') . '") AS `biller`')
    ->addSelect('`accounts_authentications`.`ip`')
    ->addSelect('`accounts_authentications`.`action`')
    ->addSelect('`accounts_authentications`.`method`');

if ($filters->getDateStart()) {
    $query->addWhere('`accounts_authentications`.`created_on` >= :start', [':start' => $filters->getDateStart()->format('mysql')]);
}

if ($filters->getDateStop()) {
    $query->addWhere('`accounts_authentications`.`created_on` >= :start', [':stop' => $filters->getDateStop()->format('mysql')]);
}


// Build the incidents card
$incidents_card = Card::new()
                      ->setTitle('Authentications')
                      ->setSwitches('reload')
                      ->setContent($table->getHtmlDataTableObject()
                                         ->setRowUrl('/security/authentication+:ROW.html'))
                      ->useForm(true);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Authentications'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    ''               => tr('Authentications'),
]));


// Build and render the page grid
$grid = Grid::new()
            ->addGridColumn($filters_card->render() . $incidents_card->render(), EnumDisplaySize::nine)
            ->addGridColumn($relevant_card->render() . '<br>' . $documentation_card->render(), EnumDisplaySize::three);

return $grid->render();
