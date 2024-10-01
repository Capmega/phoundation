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
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// This page allows post
Request::getMethodRestrictionsObject()->allow(EnumHttpRequestMethod::post);


// Build users filter card
$filters      = FilterForm::new();
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
                               ->setId('results')
                               ->setCheckboxSelectors(EnumTableIdColumn::visible);

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
                     ->setContent('<a href="' . Url::getWww('/reports.html') . '">' . tr('Reports') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('<p>This manual query report generator allows you to generate any type of report manually by typing the query</p>
                                        <p>The query interface does NOT allow for insert or update queries</p>
                                        <p>Query results containing columns with password information will be automatically filtered</p>');


// Set page meta data
Response::setHeaderTitle(tr('SQL report'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/reports.html'  => tr('Reports'),
    ''               => tr('SQL report'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card->render()  . $results_card->render()      , EnumDisplaySize::nine)
           ->addGridColumn($relevant_card->render() . $documentation_card->render(), EnumDisplaySize::three);
