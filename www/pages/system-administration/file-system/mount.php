<?php

declare(strict_types=1);

use Phoundation\Filesystem\Mounts\Mount;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page file-system/mounts/mount.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


// Validate GET and get the requested mount
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$mount = Mount::get($get['id']);


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                    ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update mount, roles, emails, and phones
                $mount->apply(false)->save();

                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been saved', [
                    ':mount' => $mount->getDisplayName()
                ]));

                // Redirect away from POST
                Page::redirect(UrlBuilder::getWww('/system-administration/file-system/mounts/mount-' . $mount->getId() . '.html'));

            case tr('Delete'):
                $mount->delete();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been deleted', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Page::redirect();

            case tr('Lock'):
                $mount->lock();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been locked', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Page::redirect();

            case tr('Unlock'):
                $mount->unlock();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been unlocked', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Page::redirect();

            case tr('Undelete'):
                $mount->undelete();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been undeleted', [
                    ':mount' => $mount->getDisplayName()
                ]));

                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
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
            ->setMode(DisplayMode::warning)
            ->setOutlined(true)
            ->setValue(tr('Undelete'))
            ->setContent(tr('Undelete'));

    } else {
        $delete = Button::new()
            ->setFloatRight(true)
            ->setMode(DisplayMode::warning)
            ->setOutlined(true)
            ->setValue(tr('Delete'))
            ->setContent(tr('Delete'));

        if ($mount->isLocked()) {
            $lock = Button::new()
                ->setFloatRight(true)
                ->setMode(DisplayMode::warning)
                ->setValue(tr('Unlock'))
                ->setContent(tr('Unlock'));

        } else {
            $lock = Button::new()
                ->setFloatRight(true)
                ->setMode(DisplayMode::warning)
                ->setValue(tr('Lock'))
                ->setContent(tr('Lock'));
        }

        // Audit button.
        $audit = Button::new()
            ->setFloatRight(true)
            ->setMode(DisplayMode::information)
            ->setAnchorUrl('/audit/meta-' . $mount->getMeta() . '.html')
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
    ->setContent($mount->getHtmlDataEntryForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(isset_get($save))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/system-administration/file-system/mounts/mounts.html'), true)
        ->addButton(isset_get($audit))
        ->addButton(isset_get($delete))
        ->addButton(isset_get($lock))
        ->addButton(isset_get($impersonate)));


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Mount profile picture'))
    ->setContent(Img::new()
        ->addClass('w100')
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($mount->getPicture())
        ->setAlt(tr('Profile picture for :mount', [':mount' => $mount->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
->setContent('<a href="' . UrlBuilder::getWww('/system-administration/file-system/filesystem.html') . '">' . tr('Manage filesystem') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setPageTitle(tr('Mount :mount', [':mount' => $mount->getDisplayName()]));
Page::setHeaderTitle(tr('Mount'));
Page::setHeaderSubTitle($mount->getDisplayName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                                     => tr('Home'),
    '/system-administration.html'                           => tr('System administration'),
    '/filesystem.html'                                      => tr('Filesystem'),
    '/system-administration/file-system/mounts/mounts.html' => tr('Mounts'),
    ''                                                      => $mount->getDisplayName()
]));
