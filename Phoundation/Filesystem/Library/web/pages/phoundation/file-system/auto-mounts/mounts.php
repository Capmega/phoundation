<?php

/**
 * Page file-system/mounts.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Mounts\FilterForm;
use Phoundation\Filesystem\Mounts\PhoMounts;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
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
$o_filters      = FilterForm::new();
$o_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Filters')
                      ->setContent($o_filters);


// Button clicked?
if (Request::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
                         ->select('filesystem_mounts_length')->isOptional()->isNumeric()    // This is paging length, ignore
                         ->select('submit-button')->isOptional()->isVariable()
                         ->select('id')->isOptional()->isArray()->forEachField()->isDbId()
                         ->validate();

    try {
        // Process buttons
        switch ($post['submit-button']) {
            case tr('Delete'):
                // Delete selected mounts
                $count = PhoMounts::directOperations()->deleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Deleted ":count" mounts', [':count' => $count]));
                Response::redirect('this');

            case tr('Undelete'):
                // Undelete selected mounts
                $count = PhoMounts::directOperations()->undeleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Undeleted ":count" mounts', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Get the mounts list and apply filters
$o_mounts  = PhoMounts::new();
$o_builder = $o_mounts->getQueryBuilderObject()
                      ->addSelect('`filesystem_mounts`.`id`, 
                                   `filesystem_mounts`.`name`, 
                                   `filesystem_mounts`.`filesystem`, 
                                   `filesystem_mounts`.`source_path`, 
                                   `filesystem_mounts`.`target_path`, 
                                   `filesystem_mounts`.`status`, 
                                   `filesystem_mounts`.`created_on`');

switch ($o_filters->get('status')) {
    case '__all':
        break;

    case null:
        $o_builder->addWhere('`filesystem_mounts`.`status` IS NULL');
        break;

    default:
        $o_builder->addWhere('`filesystem_mounts`.`status` = :status', [':status' => $o_filters->get('status')]);
}


// Build SQL mounts table
$o_buttons = Buttons::new()
                    ->addCreateButton(Url::new('/phoundation/file-system/mount.html'))
                    ->addDeleteButton(true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$o_mounts_card = Card::new()
                     ->setTitle('Available mounts')
                     ->setSwitches('reload')
                     ->setContent($o_mounts->load()
                                           ->getHtmlDataTableObject()->setRowUrls('/phoundation/file-system/mount+:ROW.html')
                                           ->setOrder([1 => 'asc']))
                     ->useForm(true)
                     ->setButtonsObject($o_buttons);

$o_mounts_card->getFormObject()
              ->setAction(Url::newCurrent())
              ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/phoundation/file-system/roles.html')->makeWww(), tr('Filesystem connectors management')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta-data
Response::setHeaderTitle(tr('Filesystem mounts'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'                          , tr('Home')),
    Breadcrumb::new('/system-administration.html', tr('System administration')),
    Breadcrumb::new('/filesystem.html'           , tr('Filesystem')),
    Breadcrumb::new(''                           , tr('FsMounts'))
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card . $o_mounts_card, EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card                , EnumDisplaySize::three);
