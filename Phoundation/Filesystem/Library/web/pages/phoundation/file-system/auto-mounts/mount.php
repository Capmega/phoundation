<?php

/**
 * Page file-system/mount.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Mounts\FsMount;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
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

$mount = FsMount::new($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                    ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update mount, roles, emails, and phones
                $mount->apply(false)->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been saved', [
                    ':mount' => $mount->getDisplayName()
                ]));

                // Redirect away from POST
                Response::redirect(Url::getWww('/phoundation/file-system/mount+' . $mount->getId() . '.html'));

            case tr('Delete'):
                $mount->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been deleted', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $mount->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The mount ":mount" has been undeleted', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $mount->forceApply();
    }
}


// Save button
if (!$mount->getReadonly()) {
    $save = Button::new()
                  ->setValue(tr('Save'))
                  ->setContent(tr('Save'));
}


// Delete button.
if (!$mount->isNew()) {
    if ($mount->isDeleted()) {
        $delete = Button::new()
                        ->setFloatRight(true)
                        ->setMode(EnumDisplayMode::warning)
                        ->setOutlined(true)
                        ->setValue(tr('Undelete'))
                        ->setContent(tr('Undelete'));

    } else {
        $delete = Button::new()
                        ->setFloatRight(true)
                        ->setMode(EnumDisplayMode::warning)
                        ->setOutlined(true)
                        ->setValue(tr('Delete'))
                        ->setContent(tr('Delete'));

        // Audit button.
        $audit = Button::new()
                       ->setFloatRight(true)
                       ->setMode(EnumDisplayMode::information)
                       ->setAnchorUrl('/audit/meta+' . $mount->getMetaId() . '.html')
                       ->setFloatRight(true)
                       ->setValue(tr('Audit'))
                       ->setContent(tr('Audit'));
    }
}


// Build the mount form
$mount_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle(tr('Edit mount :name', [':name' => $mount->getDisplayName()]))
    ->setContent($mount->getHtmlDataEntryFormObject()->render())
    ->setButtons(Buttons::new()
                        ->addButton(isset_get($save))
                        ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/phoundation/file-system/mounts.html'), true)
                        ->addButton(isset_get($audit))
                        ->addButton(isset_get($delete))
                        ->addButton(isset_get($impersonate)));


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('FsMount profile picture'))
    ->setContent(Img::new()
        ->addClasses('w100')
        ->setSrc(Url::getImg('img/profiles/default.png'))
//        ->setSrc($mount->getPicture())
        ->setAlt(tr('Profile picture for :mount', [':mount' => $mount->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
->setContent('<a href="' . Url::getWww('/phoundation/file-systems.html') . '">' . tr('Manage filesystems') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn(GridColumn::new()
        // The mount card and all additional cards
        ->addContent($mount_card->render())
        ->setSize(9)
        ->useForm(true))
    ->addColumn($picture->render() . '<br>' . $relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setPageTitle(tr('FsMount :mount', [':mount' => $mount->getDisplayName()]));
Response::setHeaderTitle(tr('FsMount'));
Response::setHeaderSubTitle($mount->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                               => tr('Home'),
    '/system-administration.html'                     => tr('System administration'),
    '/phoundation/file-systems.html'        => tr('Filesystems'),
    '/phoundation/file-systems/mounts.html' => tr('FsMounts'),
    ''                                                => $mount->getDisplayName()
]));
