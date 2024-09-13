<?php

/**
 * Page report/sql
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\FilterForm;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// This page allows post
Request::getMethodRestrictionsObject()->allow(EnumHttpRequestMethod::post);


// Build users filter card
$filters      = FilterForm::new()->apply();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('SQL query filter')
                    ->setContent($filters)
                    ->useForm(true);


// Only allow SHOW and SELECT queries
$query = $filters->getQuery();

// Build result table
try {
    if ($query) {
        $query = strtolower($query);

        if (str_contains($query, 'insert into') or str_contains($query, 'UPDATE ')) {
            throw new ValidationFailedException(tr('Sorry, INSERT and or UPDATE queries are now allowed'));
        }

        if (str_contains($query, 'password')) {
            throw new ValidationFailedException(tr('Sorry, password data cannot be retrieved'));
        }
    }

    $results = DataIterator::new()
        ->setQuery($filters->getQuery())
        ->getHtmlDataTableObject()
        ->setCheckboxSelectors(EnumTableIdColumn::hidden);

} catch (SqlException|ValidationFailedException $e) {
    Response::getFlashMessagesObject()->addWarning($e->getMessage());
    $results = null;
}


// Build result card
$results_card = Card::new()
                    ->setTitle('SQL query report')
                    ->setSwitches('reload')
                    ->setContent($results);


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


// Build and render the page grid
$grid = Grid::new()
    ->addGridColumn($filters_card->render() . $results_card->render(), EnumDisplaySize::nine)
    ->addGridColumn($relevant_card->render() . '<br>' . $documentation_card->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('SQL report'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/reports.html'  => tr('Reports'),
    ''               => tr('SQL report'),
]));
