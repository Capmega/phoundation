<?php

declare(strict_types=1);

use Phoundation\Filesystem\Requirements\Requirement;
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
 * Page file-system/requirements/requirement.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


// Validate GET and get the requested requirement
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$requirement = Requirement::get($get['id'], no_identifier_exception: false);


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                    ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update requirement, roles, emails, and phones
                $requirement->apply(false)->save();

                Page::getFlashMessages()->addSuccessMessage(tr('The requirement ":requirement" has been saved', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                // Redirect away from POST
                Page::redirect(UrlBuilder::getWww('/phoundation/file-system/requirements/requirement+' . $requirement->getId() . '.html'));

            case tr('Delete'):
                $requirement->delete();
                Page::getFlashMessages()->addSuccessMessage(tr('The requirement ":requirement" has been deleted', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Page::redirect();

            case tr('Lock'):
                $requirement->lock();
                Page::getFlashMessages()->addSuccessMessage(tr('The requirement ":requirement" has been locked', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Page::redirect();

            case tr('Unlock'):
                $requirement->unlock();
                Page::getFlashMessages()->addSuccessMessage(tr('The requirement ":requirement" has been unlocked', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Page::redirect();

            case tr('Undelete'):
                $requirement->undelete();
                Page::getFlashMessages()->addSuccessMessage(tr('The requirement ":requirement" has been undeleted', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
        $requirement->forceApply();
    }
}


// Save button
if (!$requirement->getReadonly()) {
    $save = Button::new()
        ->setValue(tr('Save'))
        ->setContent(tr('Save'));
}


// Delete button.
if (!$requirement->isNew()) {
    if ($requirement->isDeleted()) {
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

        if ($requirement->isLocked()) {
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
            ->setAnchorUrl('/audit/meta+' . $requirement->getMetaId() . '.html')
            ->setFloatRight(true)
            ->setValue(tr('Audit'))
            ->setContent(tr('Audit'));
    }
}


// Build the requirement form
$requirement_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle(tr('Edit requirement :name', [':name' => $requirement->getDisplayName()]))
    ->setContent($requirement->getHtmlDataEntryForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(isset_get($save))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/phoundation/file-system/requirements/requirements.html'), true)
        ->addButton(isset_get($audit))
        ->addButton(isset_get($delete))
        ->addButton(isset_get($lock))
        ->addButton(isset_get($impersonate)));


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Requirement profile picture'))
    ->setContent(Img::new()
        ->addClass('w100')
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($requirement->getPicture())
        ->setAlt(tr('Profile picture for :requirement', [':requirement' => $requirement->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
->setContent('<a href="' . UrlBuilder::getWww('/phoundation/file-systems.html') . '">' . tr('Manage filesystems') . '</a><br>');


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
        // The requirement card and all additional cards
        ->addContent($requirement_card->render())
        ->setSize(9)
        ->useForm(true))
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setPageTitle(tr('Requirement :requirement', [':requirement' => $requirement->getDisplayName()]));
Page::setHeaderTitle(tr('Requirement'));
Page::setHeaderSubTitle($requirement->getDisplayName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                               => tr('Home'),
    '/system-administration.html'                     => tr('System administration'),
    '/phoundation/file-systems.html'        => tr('Filesystems'),
    '/phoundation/file-systems/requirements.html' => tr('Requirements'),
    ''                                                => $requirement->getDisplayName()
]));
