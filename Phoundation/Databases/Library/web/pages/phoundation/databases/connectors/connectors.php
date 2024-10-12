<?php

/**
 * Page databases/connectors/connectors.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\FilterForm;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Build the "filters" card
$filters      = FilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Filters')
                    ->setContent($filters);


// Button clicked?
if (Request::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
                         ->select('databases_connectors_length')->isOptional()->isNumeric()    // This is paging length, ignore
                         ->select('submit')->isOptional()->isVariable()
                         ->select('id')->isOptional()->isArray()->eachField()->isDbId()
                         ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected connectors
                $count = Connectors::directOperations()->deleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Deleted ":count" connectors', [':count' => $count]));
                Response::redirect('this');

            case tr('Undelete'):
                // Undelete selected connectors
                $count = Connectors::directOperations()->undeleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Undeleted ":count" connectors', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Get the connectors list and apply filters
$connectors = Connectors::new();
$builder    = $connectors->getQueryBuilder()
                         ->addSelect('`databases_connectors`.`id`, 
                 `databases_connectors`.`name`, 
                 `databases_connectors`.`hostname`, 
                 `databases_connectors`.`username`, 
                 `databases_connectors`.`database`, 
                 `databases_connectors`.`status`, 
                 `databases_connectors`.`created_on`');

switch ($filters->get('status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`databases_connectors`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`databases_connectors`.`status` = :status', [':status' => $filters->get('status')]);
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
                                        ->getHtmlDataTableObject()
                                        ->setRowUrl('/phoundation/databases/connectors/connector+:ROW.html')
                                        ->setColumns('id,name,hostname,username,database,status,created_on')
                                        ->setOrder([1 => 'asc']))
                       ->useForm(true)
                       ->setButtons($buttons);

$connectors_card->getForm()
                ->setAction(Url::getCurrent())
                ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/phoundation/databases/connectors/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . Url::getWww('/phoundation/databases/connectors/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Render and return the page grid
$grid = Grid::new()
            ->addGridColumn($filters_card . $connectors_card, EnumDisplaySize::nine)
            ->addGridColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Database connectors'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                           => tr('Home'),
                                                           '/system-administration.html' => tr('System administration'),
                                                           '/phoundation/databases.html' => tr('Databases'),
                                                           ''                            => tr('Connectors'),
                                                       ]));
