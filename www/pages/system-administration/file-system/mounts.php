<?php

declare(strict_types=1);

use Phoundation\Filesystem\Mounts\FilterForm;
use Phoundation\Filesystem\Mounts\Mounts;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\ButtonType;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page filesystem/mounts.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


// Build the page content
// Build mounts filter card
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
        ->select('filesystem_mounts_length')->isOptional()->isNumeric()    // This is paging length, ignore
        ->select('submit')->isOptional()->isVariable()
        ->select('id')->isOptional()->isArray()->each()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Mount'):
                // Delete selected mounts
                $count = Mounts::directOperations()->deleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Mounted ":count" mounts', [':count' => $count]));
                Page::redirect('this');

            case tr('Unmount'):
                // Undelete selected mounts
                $count = Mounts::directOperations()->undeleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Unmounted ":count" mounts', [':count' => $count]));
                Page::redirect('this');

            case tr('Test'):
                // Test selected mounts
                $count = Mounts::directOperations()->undeleteKeys($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Undeleted ":count" mounts', [':count' => $count]));
                Page::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on the page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Get the mounts list and apply filters
$mounts   = Mounts::new();
$builder = $mounts->getQueryBuilder()
    ->addSelect('`filesystem_mounts`.`id`, 
                 `filesystem_mounts`.`name`, 
                 `filesystem_mounts`.`status`, 
                 `filesystem_mounts`.`created_on`')
    ->addGroupBy('`filesystem_mounts`.`id`');

switch ($filters->getSourceKey('entry_status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`filesystem_mounts`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`filesystem_mounts`.`status` = :status', [':status' => $filters->getSourceKey('entry_status')]);
}


// Build mounts table
$buttons = Buttons::new()
    ->addButton(tr('Create'), DisplayMode::primary, '/filesystem/mount.html')
    ->addButton(tr('Delete'), DisplayMode::warning, ButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$mounts_card = Card::new()
    ->setTitle('Active mounts')
    ->setSwitches('reload')
    ->setContent($mounts
        ->load()
        ->getHtmlDataTable()
            ->setDateFormat('YYYY-MM-DD HH:mm:ss')
            ->setRowUrl('/filesystem/mount-:ROW.html')
            ->setOrder([1 => 'asc']))
    ->useForm(true)
    ->setButtons($buttons);

$mounts_card->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'));


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters_card->render() . $mounts_card->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Mounts'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                => tr('Home'),
    '/filesystem.html' => tr('Filesystem'),
    ''                 => tr('Mounts')
]));