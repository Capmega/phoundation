<?php

/**
 * Page file-system/requirements/requirement.php
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
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Filesystem\Requirements\Requirement;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\LockButton;
use Phoundation\Web\Html\Components\Input\Buttons\SaveButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\UnlockButton;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET and get the requested requirement
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$_requirement = Requirement::new()->loadThis($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                                     ->select('roles_id')->isOptional()->isArray()->forEachField()->isOptional()->isDbId()
                                     ->validate(false);

                // Update requirement, roles, emails, and phones
                $_requirement->apply(false)->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The requirement ":requirement" has been saved', [
                    ':requirement' => $_requirement->getDisplayName()
                ]));

                // Redirect away from POST
                Response::redirect(Url::new('/phoundation/file-system/requirements/requirement+' . $_requirement->getId() . '.html')->makeWww());

            case tr('Delete'):
                $_requirement->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The requirement ":requirement" has been deleted', [
                    ':requirement' => $_requirement->getDisplayName()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $_requirement->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The requirement ":requirement" has been undeleted', [
                    ':requirement' => $_requirement->getDisplayName()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $_requirement->forceApply();
    }
}


// Save button
if (!$_requirement->getReadonly()) {
    $_save = SaveButton::new();
}


// Delete button.
if (!$_requirement->isNew()) {
    if ($_requirement->isDeleted()) {
        $_delete = UndeleteButton::new();

    } else {
        $_delete = DeleteButton::new();

        if ($_requirement->isLocked()) {
            $_lock = UnlockButton::new();

        } else {
            $_lock = LockButton::new();
        }

        // Audit button.
        $_audit = AuditButton::new()
                              ->setFloatRight(true)
                              ->setUrlObject('/audit/meta+' . $_requirement->getMetaId() . '.html');
    }
}


// Build the "requirement" form
$_requirement_card = Card::new()
                        ->setCollapseSwitch(true)
                        ->setMaximizeSwitch(true)
                        ->setTitle(tr('Edit requirement :name', [':name' => $_requirement->getDisplayName()]))
                        ->setContent($_requirement->getHtmlDataEntryFormObject())
                        ->setButtonsObject(Buttons::new()
                                                  ->addButton(isset_get($_save))
                                                  ->addBackButton(Url::newPrevious('/phoundation/file-system/requirements/requirements.html'), true)
                                                  ->addButton(isset_get($_audit))
                                                  ->addButton(isset_get($_delete))
                                                  ->addButton(isset_get($_lock))
                                                  ->addButton(isset_get($_impersonate)));


// Build profile picture card
$_picture = Card::new()
                 ->setTitle(tr('Requirement profile picture'))
                 ->setContent(Img::new()
                                 ->addClasses('w100')
                                 ->setSrc(Url::new('img/profiles/default.png')->makeImg())
//                               ->setSrc($_requirement->getPicture())
                                 ->setAlt(tr('Profile picture for :requirement', [':requirement' => $_requirement->getDisplayName()])));


// Build relevant links
$_relevant = Card::new()
                  ->setMode(EnumDisplayMode::info)
                  ->setTitle(tr('Relevant links'))
                  ->setContent(AnchorBlock::new(Url::new('/phoundation/file-systems.html')->makeWww(), tr('Manage filesystems')));


// Build documentation
$_documentation = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Documentation'))
                       ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                     <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                     <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta-data
Response::setPageTitle(tr('Requirement :requirement', [':requirement' => $_requirement->getDisplayName()]));
Response::setHeaderTitle(tr('Requirement'));
Response::setHeaderSubTitle($_requirement->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                                          , tr('Home')),
    Breadcrumb::new('/system-administration.html'                , tr('System administration')),
    Breadcrumb::new('/phoundation/file-systems.html'             , tr('Filesystems')),
    Breadcrumb::new('/phoundation/file-systems/requirements.html', tr('Requirements')),
    Breadcrumb::new(''                                           , $_requirement->getDisplayName()),
]);


// Return the page grid
return Grid::new()
           ->addGridColumn(GridColumn::new()
                                     // The requirement card and all additional cards
                                     ->addContent($_requirement_card)
                                     ->setSize(9)
                                     ->useForm(true))
           ->addGridColumn($_picture . $_relevant . $_documentation, EnumDisplaySize::three);
