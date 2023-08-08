<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Button;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page accounts/user.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Validate GET
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$user = User::get($get['id']);


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                    ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update user and roles
                $user
                    ->apply()
                    ->save()
                    ->getRoles()
                        ->set($post['roles_id']);

// TODO Implement timers
//showdie(Timers::get('query'));

                Page::getFlashMessages()->addSuccessMessage(tr('User ":user" has been saved', [':user' => $user->getDisplayName()]));
                Page::redirect('referer');

            case tr('Impersonate'):
                $user->impersonate();
                Page::getFlashMessages()->addSuccessMessage(tr('You are now impersonating ":user"', [':user' => $user->getDisplayName()]));
                Page::redirect('root');

            case tr('Delete'):
                $user->delete();
                Page::getFlashMessages()->addSuccessMessage(tr('The user ":user" has been deleted', [':user' => $user->getDisplayName()]));
                Page::redirect();

            case tr('Undelete'):
                $user->undelete();
                Page::getFlashMessages()->addSuccessMessage(tr('The user ":user" has been undeleted', [':user' => $user->getDisplayName()]));
                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);

        $user->forceApply();
    }
}


// Save button
if (!$user->getReadonly()) {
    $save = Button::new()
        ->setValue(tr('Save'))
        ->setContent(tr('Save'));
}


// Impersonate button. We must have the right to impersonate, we cannot impersonate ourselves, and we cannot impersonate
// god users
if ($user->canBeImpersonated()) {
    $impersonate = Button::new()
        ->setRight(true)
        ->setMode(DisplayMode::danger)
        ->setValue(tr('Impersonate'))
        ->setContent(tr('Impersonate'));
}


// Delete button. We cannot delete god users
if ($user->canBeStatusChanged()) {
    $delete = Button::new()
        ->setRight(true)
        ->setMode(DisplayMode::warning)
        ->setOutlined(true)
        ->setValue(tr('Delete'))
        ->setContent(tr('Delete'));
}


// Audit button. We cannot delete god users
if (!$user->isNew()) {
    $audit = Button::new()
        ->setRight(true)
        ->setMode(DisplayMode::information)
        ->setAnchorUrl('/audit/meta-' . $user->getMeta() . '.html')
        ->setRight(true)
        ->setValue(tr('Audit'))
        ->setContent(tr('Audit'));
}


// Build the user form
$user_card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Edit data for user :name', [':name' => $user->getDisplayName()]))
    ->setContent($user->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(isset_get($save))
        ->addButton(tr('Back'), DisplayMode::secondary, 'prev', true)
        ->addButton(isset_get($audit))
        ->addButton(isset_get($delete))
        ->addButton(isset_get($impersonate)));


// Build the roles list management section
if ($user->getId()) {
    $roles_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Roles for this user [:count]', [':count' => $user->getRoles()->getCount()]))
        ->setContent($user->getRolesHtmlForm()
            ->setAction('#')
            ->setMethod('POST')
            ->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Save'))
            ->addButton(tr('Back'), DisplayMode::secondary, 'prev', true));

    $rights_card = Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr('Rights for this user [:count]', [':count' => $user->getRights()->getCount()]))
        ->setDescription(tr('This is a list of rights that this user has available because of its assigned roles. Each role gives the user a certain amount of rights and with adding or removing roles, you add or remove these rights. These rights are used to determine the access to pages or specific information that a user has. To determine what rights are required to access a specific page, click the "lock" symbol at the top menu.'))
        ->setContent($user->getRights()
                            ->getHtmlDataTable('id,name,description')
                            ->render());
}


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($user_card->render() . (isset($roles_card) ? $roles_card->render() : '') . (isset($rights_card) ? $rights_card->render() : ''))
    ->setSize(9)
    ->useForm(true);


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('User profile picture'))
    ->setContent(Img::new()
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($user->getPicture())
        ->setAlt(tr('Profile picture for :user', [':user' => $user->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/password-' . $user->getId() . '.html') . '">' . tr('Change password for this user') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('User'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/accounts/users.html' => tr('Users'),
    ''                     => $user->getDisplayName()
]));
