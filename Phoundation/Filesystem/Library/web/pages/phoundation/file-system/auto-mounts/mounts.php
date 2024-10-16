<?php

/**
 * Page file-system/mounts.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Mounts\FilterForm;
use Phoundation\Filesystem\Mounts\FsMounts;
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
        ->select('filesystem_mounts_length')->isOptional()->isNumeric()    // This is paging length, ignore
        ->select('submit')->isOptional()->isVariable()
        ->select('id')->isOptional()->isArray()->eachField()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected mounts
                $count = FsMounts::directOperations()->deleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Deleted ":count" mounts', [':count' => $count]));
                Response::redirect('this');

            case tr('Undelete'):
                // Undelete selected mounts
                $count = FsMounts::directOperations()->undeleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Undeleted ":count" mounts', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Get the mounts list and apply filters
$mounts   = FsMounts::new();
$builder = $mounts->getQueryBuilder()
    ->addSelect('`filesystem_mounts`.`id`, 
                 `filesystem_mounts`.`name`, 
                 `filesystem_mounts`.`filesystem`, 
                 `filesystem_mounts`.`source_path`, 
                 `filesystem_mounts`.`target_path`, 
                 `filesystem_mounts`.`status`, 
                 `filesystem_mounts`.`created_on`');

switch ($filters->get('status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`filesystem_mounts`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`filesystem_mounts`.`status` = :status', [':status' => $filters->get('status')]);
}

// Build SQL mounts table
$buttons = Buttons::new()
                  ->addButton(tr('Create'), EnumDisplayMode::primary, '/phoundation/file-system/mount.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$mounts_card = Card::new()
    ->setTitle('Available mounts')
    ->setSwitches('reload')
    ->setContent($mounts
        ->load()
        ->getHtmlDataTableObject()
            ->setRowUrl('/phoundation/file-system/mount+:ROW.html')
            ->setOrder([1 => 'asc']))
    ->useForm(true)
    ->setButtons($buttons);

$mounts_card->getForm()
            ->setAction(Url::getCurrent())
            ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant_card = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . Url::getWww('/phoundation/file-system/roles.html') . '">' . tr('Filesystem connectors management') . '</a><br>');


// Build documentation
$documentation_card = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Filesystem mounts'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                           => tr('Home'),
    '/system-administration.html' => tr('System administration'),
    '/filesystem.html'            => tr('Filesystem'),
    ''                            => tr('FsMounts')
]));


// Render and return the page grid
return Grid::new()
    ->addGridColumn($filters_card  . $mounts_card  , EnumDisplaySize::nine)
    ->addGridColumn($relevant_card . $documentation, EnumDisplaySize::three);
