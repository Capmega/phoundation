<?php

/**
 * Page file-system/mount.php
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
use Phoundation\Filesystem\Mounts\PhoMount;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\SaveButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET and get the requested mount
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$o_mount = PhoMount::new()->loadThis($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                                     ->select('roles_id')->isOptional()->isArray()->forEachField()->isOptional()->isDbId()
                    ->validate(false);

                // Update mount, roles, emails, and phones
                $o_mount->apply(false)->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been saved', [
                    ':mount' => $o_mount->getDisplayName()
                ]));

                // Redirect away from POST
                Response::redirect(Url::new('/phoundation/file-system/mount+' . $o_mount->getId() . '.html')->makeWww());

            case tr('Delete'):
                $o_mount->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been deleted', [
                    ':mount' => $o_mount->getDisplayName()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $o_mount->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been undeleted', [
                    ':mount' => $o_mount->getDisplayName()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $o_mount->forceApply();
    }
}


// Save button
if (!$o_mount->getReadonly()) {
    $o_save = SaveButton::new();
}


// Delete button.
if (!$o_mount->isNew()) {
    if ($o_mount->isDeleted()) {
        $o_delete = UndeleteButton::new()->setFloatRight(true);

    } else {
        $o_delete = DeleteButton::new()->setFloatRight(true);

        // Audit button.
        $o_audit = AuditButton::new()
                              ->setFloatRight(true)
                              ->setUrlObject('/audit/meta+' . $o_mount->getMetaId() . '.html');
    }
}


// Build the "mount" form
$o_mount_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setMaximizeSwitch(true)
                    ->setTitle(tr('Edit mount :name', [':name' => $o_mount->getDisplayName()]))
                    ->setContent($o_mount->getHtmlDataEntryFormObject())
                    ->setButtonsObject(Buttons::new()->addButton(isset_get($o_save))
                                                     ->addBackButton(Url::newPrevious('/phoundation/file-system/mounts.html'), true)
                                                     ->addButton(isset_get($o_audit))
                                                     ->addButton(isset_get($o_delete)));


// Build profile picture card
$o_picture = Card::new()
                 ->setTitle(tr('FsMount profile picture'))
                 ->setContent(Img::new()
                                 ->addClasses('w100')
                                 ->setSrc(Url::new('img/profiles/default.png')->makeImg())
                                 //->setSrc($mount->getPicture())
                                 ->setAlt(tr('Profile picture for :mount', [':mount' => $o_mount->getDisplayName()])));


// Build relevant links
$o_relevant = Card::new()
                  ->setMode(EnumDisplayMode::info)
                  ->setTitle(tr('Relevant links'))
                  ->setContent(AnchorBlock::new(Url::new('/phoundation/file-systems.html')->makeWww(), tr('Manage filesystems')));


// Build documentation
$o_documentation = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Documentation'))
                       ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                     <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                     <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta-data
Response::setPageTitle(tr('FsMount :mount', [':mount' => $o_mount->getDisplayName()]));
Response::setHeaderTitle(tr('FsMount'));
Response::setHeaderSubTitle($o_mount->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                                    , tr('Home')),
    Breadcrumb::new('/system-administration.html'          , tr('System administration')),
    Breadcrumb::new('/phoundation/file-systems.html'       , tr('Filesystems')),
    Breadcrumb::new('/phoundation/file-systems/mounts.html', tr('FsMounts')),
    Breadcrumb::new(''                                     , $o_mount->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn(GridColumn::new()
                                     ->addContent($o_mount_card)
                                     ->setSize(9)
                                     ->useForm(true))
           ->addGridColumn($o_picture . $o_relevant . $o_documentation, EnumDisplaySize::three);
