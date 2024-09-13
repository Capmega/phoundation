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
use Phoundation\Web\Requests\Response;


// Build users filter card
$filters      = FilterForm::new()->apply();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Incidents filters')
                    ->setContent($filters->render());


// Build the incident table
$table = Incidents::new();
$query = $table->getQueryBuilder()
    ->addSelect('`security_incidents`.`id`')
    ->addSelect('`security_incidents`.`type`')
    ->addSelect('`security_incidents`.`created_on`')
    ->addSelect('`security_incidents`.`severity`')
    ->addSelect('`security_incidents`.`title`');

if ($filters->getDateStart()) {
    $query->addWhere('`security_incidents`.`created_on` >= :start', [':start' => $filters->getDateStart()->format('mysql')]);
}

if ($filters->getDateStop()) {
    $query->addWhere('`security_incidents`.`created_on` >= :start', [':stop' => $filters->getDateStop()->format('mysql')]);
}

$query->addWhere(SqlQueries::is('`security_incidents`.`status`', null, ':status'));

if ($filters->getSeverities()) {
    $values = SqlQueries::in($filters->getSeverities());
    $query->addWhere('`security_incidents`.`severity` IN (' . SqlQueries::inColumns($values) . ')', $values);
}


// Build the incidents card
$incidents_card = Card::new()
                      ->setTitle('Security incidents')
                      ->setSwitches('reload')
                      ->setContent($table->getHtmlDataTableObject()
                                         ->setRowUrl('/security/incident+:ROW.html'))
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
Response::setHeaderTitle(tr('Incidents'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    ''               => tr('Incidents'),
]));


// Build and render the page grid
return Grid::new()
           ->addGridColumn($filters_card->render() . $incidents_card->render(), EnumDisplaySize::nine)
           ->addGridColumn($relevant_card->render() . '<br>' . $documentation_card->render(), EnumDisplaySize::three);
