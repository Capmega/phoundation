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
use Phoundation\Web\Html\Enums\TableIdColumn;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page system-administration/file-system/mount.php
 *
 * This is the primary mount management page where we can manage all the basic information about a mount 
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

$path = Mount::get($get['id']);


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
                $path->apply(false)->save();

// TODO Implement timers
//showdie(Timers::get('query'));

                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been saved', [
                    ':mount' => $path->getDisplayName()
                ]));

                // Redirect away from POST
                Page::redirect(UrlBuilder::getWww('/file-system/mount-' . $path->getId() . '.html'));

            case tr('Delete'):
                $path->delete();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been deleted', [
                    ':mount' => $path->getDisplayName()
                ]));

                Page::redirect();

            case tr('Mount'):
                $path->mount();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been mounted', [
                    ':mount' => $path->getDisplayName()
                ]));

                Page::redirect();

            case tr('Unmount'):
                $path->unmount();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been unmounted', [
                    ':mount' => $path->getDisplayName()
                ]));

                Page::redirect();

            case tr('Undelete'):
                $path->undelete();
                Page::getFlashMessages()->addSuccessMessage(tr('The mount ":mount" has been undeleted', [
                    ':mount' => $path->getDisplayName()
                ]));

                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
        $path->forceApply();
    }
}


// Save button
if (!$path->getReadonly()) {
    $save = Button::new()
        ->setValue(tr('Save'))
        ->setContent(tr('Save'));
}


// Mount button
// god mounts
if ($path->canBeMounted()) {
    if ($path->isMounted()) {
        $mount_button = Button::new()
            ->setFloatRight(true)
            ->setMode(DisplayMode::danger)
            ->setValue(tr('Unmount'))
            ->setContent(tr('Unmount'));
    } else {
        $mount_button = Button::new()
            ->setFloatRight(true)
            ->setMode(DisplayMode::danger)
            ->setValue(tr('Mount'))
            ->setContent(tr('Mount'));
    }
}


// Audit button. We cannot delete god mounts
if (!$path->isNew()) {
    $audit = Button::new()
        ->setFloatRight(true)
        ->setMode(DisplayMode::information)
        ->setAnchorUrl('/audit/meta-' . $path->getMeta() . '.html')
        ->setFloatRight(true)
        ->setValue(tr('Audit'))
        ->setContent(tr('Audit'));
}


// Build the mount form
$mount_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle(tr('Edit profile for mount :name', [':name' => $path->getDisplayName()]))
    ->setContent($path->getHtmlDataEntryForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(isset_get($save))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/file-system/mounts.html'), true)
        ->addButton(isset_get($audit))
        ->addButton(isset_get($delete))
        ->addButton(isset_get($lock))
        ->addButton(isset_get($mount_button)));


// Build the additional cards only if we're not working on a new mount
if ($path->getId()) {
    $roles_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Edit roles for this mount [:count]', [':count' => $path->getRoles()->getCount()]))
        ->setContent($path->getRolesHtmlDataEntryForm()->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Save'))
            ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/file-system/mounts.html'), true));

    $rights_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Rights for this mount [:count]', [':count' => $path->getRights()->getCount()]))
        ->setDescription(tr('This is a list of rights that this mount has available because of its assigned roles. Each role gives the mount a certain amount of rights and with adding or removing roles, you add or remove these rights. These rights are used to determine the access to pages or specific information that a mount has. To determine what rights are required to access a specific page, click the "lock" symbol at the top menu.'))
        ->setContent($path->getRights(true, true)
                            ->getHtmlDataTable('id,name,description')
                                ->setLengthChangeEnabled(false)
                                ->setSearchingEnabled(false)
                                ->setPagingEnabled(false)
                                ->setButtons('copy,csv,excel,pdf,print')
                                ->setOrder([0 => 'asc'])
                                ->setColumnsOrderable([0 => true, 1 => false])
                                ->setInfoEnabled(false)
                                ->setTableIdColumn(TableIdColumn::hidden)
                                ->render());

    $emails_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Additional email addresses for this mount [:count]', [':count' => $path->getEmails()->getCount()]))
        ->setContent($path->getEmails()->getHtmlDataEntryForm()->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Save'))
            ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/file-system/mounts.html'), true));

    $phones_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Additional phone numbers for this mount [:count]', [':count' => $path->getPhones()->getCount()]))
        ->setContent($path->getPhones()->getHtmlDataEntryForm()->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Save'))
            ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/file-system/mounts.html'), true));
}


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Mount profile picture'))
    ->setContent(Img::new()
        ->addClass('w100')
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($mount->getPicture())
        ->setAlt(tr('Profile picture for :mount', [':mount' => $path->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/file-system/password-' . $path->getId() . '.html') . '">' . tr('Change password for this mount') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/file-system/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/file-system/rights.html') . '">' . tr('Rights management') . '</a>');


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
        ->addContent($mount_card->render() .
            isset_get($roles_card)?->render() .
            isset_get($rights_card)?->render() .
            isset_get($emails_card)?->render() .
            isset_get($phones_card)?->render())
        ->setSize(9)
        ->useForm(true))
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setPageTitle(tr('Mount :mount', [':mount' => $path->getDisplayName()]));
Page::setHeaderTitle(tr('Mount'));
Page::setHeaderSubTitle($path->getDisplayName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/file-system/mounts.html' => tr('Mounts'),
    ''                     => $path->getDisplayName()
]));
