<?php

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Requirements\FilterForm;
use Phoundation\Filesystem\Requirements\Requirements;
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
 * Page file-system/requirements/requirements.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


// Build the page content
// Build requirements filter card
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
        ->select('filesystem_requirements_length')->isOptional()->isNumeric()    // This is paging length, ignore
        ->select('submit')->isOptional()->isVariable()
        ->select('id')->isOptional()->isArray()->each()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected requirements
                $count = Requirements::directOperations()->deleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Deleted ":count" requirements', [':count' => $count]));
                Page::redirect('this');

            case tr('Undelete'):
                // Undelete selected requirements
                $count = Requirements::directOperations()->undeleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Undeleted ":count" requirements', [':count' => $count]));
                Page::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Get the requirements list and apply filters
$requirements   = Requirements::new();
$builder = $requirements->getQueryBuilder()->setDebug(true)
    ->addSelect('`filesystem_requirements`.`id`, 
                 `filesystem_requirements`.`name`, 
                 `filesystem_requirements`.`path`, 
                 `filesystem_requirements`.`filesystem`, 
                 `filesystem_requirements`.`file_type`, 
                 `filesystem_requirements`.`status`, 
                 `filesystem_requirements`.`created_on`');

switch ($filters->getSourceValue('entry_status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`filesystem_requirements`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`filesystem_requirements`.`status` = :status', [':status' => $filters->getSourceValue('entry_status')]);
}

// Build SQL requirements table
$buttons = Buttons::new()
    ->addButton(tr('Create'), EnumDisplayMode::primary, '/phoundation/file-system/requirements/requirement.html')
    ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$requirements_card = Card::new()
    ->setTitle('Available requirements')
    ->setSwitches('reload')
    ->setContent($requirements
        ->load()
        ->getHtmlDataTable()
            ->setRowUrl('/phoundation/file-system/requirements/requirement+:ROW.html')
            ->setOrder([1 => 'asc']))
    ->useForm(true)
    ->setButtons($buttons);

$requirements_card->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/phoundation/file-system/roles.html') . '">' . tr('Filesystem connectors management') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters_card->render() . $requirements_card->render(), EnumDisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Filesystem requirements'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                           => tr('Home'),
    '/system-administration.html' => tr('System administration'),
    '/filesystem.html'            => tr('Filesystem'),
    ''                            => tr('Requirements')
]));
