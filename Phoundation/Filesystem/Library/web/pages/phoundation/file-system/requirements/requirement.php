<?php

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

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Requirements\Requirement;
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

// Validate GET and get the requested requirement
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$requirement = Requirement::new($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                    ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update requirement, roles, emails, and phones
                $requirement->apply(false)->save();

                Response::getFlashMessages()->addSuccess(tr('The requirement ":requirement" has been saved', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                // Redirect away from POST
                Response::redirect(UrlBuilder::getWww('/phoundation/file-system/requirements/requirement+' . $requirement->getId() . '.html'));

            case tr('Delete'):
                $requirement->delete();
                Response::getFlashMessages()->addSuccess(tr('The requirement ":requirement" has been deleted', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Response::redirect();

            case tr('Lock'):
                $requirement->lock();
                Response::getFlashMessages()->addSuccess(tr('The requirement ":requirement" has been locked', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Response::redirect();

            case tr('Unlock'):
                $requirement->unlock();
                Response::getFlashMessages()->addSuccess(tr('The requirement ":requirement" has been unlocked', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $requirement->undelete();
                Response::getFlashMessages()->addSuccess(tr('The requirement ":requirement" has been undeleted', [
                    ':requirement' => $requirement->getDisplayName()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessages()->addMessage($e);
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

        if ($requirement->isLocked()) {
            $lock = Button::new()
                          ->setFloatRight(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setValue(tr('Unlock'))
                          ->setContent(tr('Unlock'));

        } else {
            $lock = Button::new()
                          ->setFloatRight(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setValue(tr('Lock'))
                          ->setContent(tr('Lock'));
        }

        // Audit button.
        $audit = Button::new()
                       ->setFloatRight(true)
                       ->setMode(EnumDisplayMode::information)
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
    ->setContent($requirement->getHtmlDataEntryFormObject()->render())
    ->setButtons(Buttons::new()
                        ->addButton(isset_get($save))
                        ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/phoundation/file-system/requirements/requirements.html'), true)
                        ->addButton(isset_get($audit))
                        ->addButton(isset_get($delete))
                        ->addButton(isset_get($lock))
                        ->addButton(isset_get($impersonate)));


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Requirement profile picture'))
    ->setContent(Img::new()
        ->addClasses('w100')
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($requirement->getPicture())
        ->setAlt(tr('Profile picture for :requirement', [':requirement' => $requirement->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
->setContent('<a href="' . UrlBuilder::getWww('/phoundation/file-systems.html') . '">' . tr('Manage filesystems') . '</a><br>');


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
        // The requirement card and all additional cards
        ->addContent($requirement_card->render())
        ->setSize(9)
        ->useForm(true))
    ->addColumn($picture->render() . '<br>' . $relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setPageTitle(tr('Requirement :requirement', [':requirement' => $requirement->getDisplayName()]));
Response::setHeaderTitle(tr('Requirement'));
Response::setHeaderSubTitle($requirement->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                               => tr('Home'),
    '/system-administration.html'                     => tr('System administration'),
    '/phoundation/file-systems.html'        => tr('Filesystems'),
    '/phoundation/file-systems/requirements.html' => tr('Requirements'),
    ''                                                => $requirement->getDisplayName()
]));
