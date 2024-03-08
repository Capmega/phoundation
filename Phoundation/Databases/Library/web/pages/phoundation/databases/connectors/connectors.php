<?php

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\FilterForm;
use Phoundation\Web\Html\Components\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page databases/connectors/connectors.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */


// Build the page content
// Build connectors filter card
$filters      = FilterForm::new()->apply();
$filters_card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle('Filters')
    ->setContent($filters->render())
    ->useForm(true);


// Button clicked?
if (Page::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
        ->select('databases_connectors_length')->isOptional()->isNumeric()    // This is paging length, ignore
        ->select('submit')->isOptional()->isVariable()
        ->select('id')->isOptional()->isArray()->each()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected connectors
                $count = Connectors::directOperations()->deleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Deleted ":count" connectors', [':count' => $count]));
                Page::redirect('this');

            case tr('Undelete'):
                // Undelete selected connectors
                $count = Connectors::directOperations()->undeleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Undeleted ":count" connectors', [':count' => $count]));
                Page::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Get the connectors list and apply filters
$connectors = Connectors::new();
$builder    = $connectors->getQueryBuilder()->setDebug(true)
    ->addSelect('`databases_connectors`.`id`, 
                 `databases_connectors`.`name`, 
                 `databases_connectors`.`hostname`, 
                 `databases_connectors`.`username`, 
                 `databases_connectors`.`database`, 
                 `databases_connectors`.`status`, 
                 `databases_connectors`.`created_on`');

switch ($filters->get('entry_status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`databases_connectors`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`databases_connectors`.`status` = :status', [':status' => $filters->get('entry_status')]);
}

// Build SQL connectors table
$buttons = Buttons::new()
    ->addButton(tr('Create'), EnumDisplayMode::primary, '/phoundation/databases/connectors/connector.html')
    ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$connectors_card = Card::new()
    ->setTitle('Available connectors')
    ->setSwitches('reload')
    ->setContent($connectors
        ->load()
        ->getHtmlDataTable()
            ->setRowUrl('/phoundation/databases/connectors/connector+:ROW.html')
            ->setColumns('id,name,hostname,username,database,status,created_on')
            ->setOrder([1 => 'asc']))
    ->useForm(true)
    ->setButtons($buttons);

$connectors_card->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/phoundation/databases/connectors/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/phoundation/databases/connectors/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters_card->render() . $connectors_card->render(), EnumDisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Database connectors'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                     => tr('Home'),
    '/system-administration.html'           => tr('System administration'),
    '/phoundation/databases.html' => tr('Databases'),
    ''                                      => tr('Connectors')
]));
