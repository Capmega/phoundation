<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\Buttons\InputButton;
use Phoundation\Web\Html\Components\Input\Buttons\InputButtons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


/**
 * Page accounts/user.php
 *
 * This is the primary user management page where we can manage all the basic information about a user account
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


// Validate GET and get the requested user
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$user = User::new($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                                     ->select('roles_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                                     ->validate(false);

                // Update user, roles, emails, and phones
                $user->apply(false)->save();
                $user->getRoles()->setRoles($post['roles_id']);
                $user->getEmails()->apply(false)->save();
                $user->getPhones()->apply()->save();

// TODO Implement timers
//showdie(Timers::get('query'));

                Response::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been saved', [
                    ':user' => $user->getDisplayName(),
                ]));

                // Redirect away from POST
                Response::redirect(UrlBuilder::getWww('/accounts/user+' . $user->getId() . '.html'));

            case tr('Impersonate'):
                $user->impersonate();
                Response::getFlashMessages()->addSuccessMessage(tr('You are now impersonating ":user"', [
                    ':user' => $user->getDisplayName(),
                ]));

                Response::redirect('root');

            case tr('Delete'):
                $user->delete();
                Response::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been deleted', [
                    ':user' => $user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Lock'):
                $user->lock();
                Response::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been locked', [
                    ':user' => $user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Unlock'):
                $user->unlock();
                Response::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been unlocked', [
                    ':user' => $user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Undelete'):
                $user->undelete();
                Response::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been undeleted', [
                    ':user' => $user->getDisplayName(),
                ]));

                Response::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessages()->addMessage($e);
        $user->forceApply();
    }
}


// Save button
if (!$user->getReadonly()) {
    $save = InputButton::new()
                       ->setValue(tr('Save'))
                       ->setContent(tr('Save'));
}


// Impersonate button. We must have the right to impersonate, we cannot impersonate ourselves, and we cannot impersonate
// god users
if ($user->canBeImpersonated()) {
    $impersonate = InputButton::new()
                              ->setFloatRight(true)
                              ->setMode(EnumDisplayMode::danger)
                              ->setValue(tr('Impersonate'))
                              ->setContent(tr('Impersonate'));
}


// Delete button. We cannot delete god users
if ($user->canBeStatusChanged()) {
    if ($user->isDeleted()) {
        $delete = InputButton::new()
                             ->setFloatRight(true)
                             ->setMode(EnumDisplayMode::warning)
                             ->setOutlined(true)
                             ->setValue(tr('Undelete'))
                             ->setContent(tr('Undelete'));

    } else {
        $delete = InputButton::new()
                             ->setFloatRight(true)
                             ->setMode(EnumDisplayMode::warning)
                             ->setOutlined(true)
                             ->setValue(tr('Delete'))
                             ->setContent(tr('Delete'));

        if ($user->isLocked()) {
            $lock = InputButton::new()
                               ->setFloatRight(true)
                               ->setMode(EnumDisplayMode::warning)
                               ->setValue(tr('Unlock'))
                               ->setContent(tr('Unlock'));

        } else {
            $lock = InputButton::new()
                               ->setFloatRight(true)
                               ->setMode(EnumDisplayMode::warning)
                               ->setValue(tr('Lock'))
                               ->setContent(tr('Lock'));
        }
    }
}


// Audit button.
if (!$user->isNew()) {
    $audit = InputButton::new()
                        ->setFloatRight(true)
                        ->setMode(EnumDisplayMode::information)
                        ->setAnchorUrl('/audit/meta+' . $user->getMetaId() . '.html')
                        ->setFloatRight(true)
                        ->setValue(tr('Audit'))
                        ->setContent(tr('Audit'));
}


// Build the user form
$user_card = Card::new()
                 ->setCollapseSwitch(true)
                 ->setMaximizeSwitch(true)
                 ->setTitle(tr('Edit profile for user :name', [':name' => $user->getDisplayName()]))
                 ->setContent($user->getHtmlDataEntryFormObject()->render())
                 ->setButtons(InputButtons::new()
                                          ->addButton(isset_get($save))
                                          ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/users.html'), true)
                                          ->addButton(isset_get($audit))
                                          ->addButton(isset_get($delete))
                                          ->addButton(isset_get($lock))
                                          ->addButton(isset_get($impersonate)));


// Build the additional cards only if we're not working on a new user
if ($user->getId()) {
    $roles_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setCollapsed(true)
                      ->setTitle(tr('Edit roles for this user [:count]', [':count' => $user->getRoles()->getCount()]))
                      ->setContent($user->getRolesHtmlDataEntryFormObject()->render())
                      ->setButtons(InputButtons::new()
                                               ->addButton(tr('Save'))
                                               ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/users.html'), true));

    $rights_card = Card::new()
                       ->setCollapseSwitch(true)
                       ->setCollapsed(true)
                       ->setTitle(tr('Rights for this user [:count]', [':count' => $user->getRights()->getCount()]))
                       ->setDescription(tr('This is a list of rights that this user has available because of its assigned roles. Each role gives the user a certain number of rights and with adding or removing roles, you add or remove these rights. These rights are used to determine the access to pages or specific information that a user has. To determine what rights are required to access a specific page, click the "lock" symbol at the top menu.'))
                       ->setContent($user->getRights(true, true)
                                         ->getHtmlDataTable('id,name,description')
                                         ->setLengthChangeEnabled(false)
                                         ->setSearchingEnabled(false)
                                         ->setPagingEnabled(false)
                                         ->setButtons('copy,csv,excel,pdf,print')
                                         ->setOrder([0 => 'asc'])
                                         ->setColumnsOrderable([
                                                                   0 => true,
                                                                   1 => false,
                                                               ])
                                         ->setInfoEnabled(false)
                                         ->setCheckboxSelectors(EnumTableIdColumn::hidden)
                                         ->render());

    $emails_card = Card::new()
                       ->setCollapseSwitch(true)
                       ->setCollapsed(true)
                       ->setTitle(tr('Additional email addresses for this user [:count]', [':count' => $user->getEmails()->getCount()]))
                       ->setContent($user->getEmails()->getHtmlDataEntryFormObject()->render())
                       ->setButtons(InputButtons::new()
                                                ->addButton(tr('Save'))
                                                ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/users.html'), true));

    $phones_card = Card::new()
                       ->setCollapseSwitch(true)
                       ->setCollapsed(true)
                       ->setTitle(tr('Additional phone numbers for this user [:count]', [':count' => $user->getPhones()->getCount()]))
                       ->setContent($user->getPhones()->getHtmlDataEntryFormObject()->render())
                       ->setButtons(InputButtons::new()
                                                ->addButton(tr('Save'))
                                                ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/users.html'), true));
}


// Build profile picture card
$picture = Card::new()
               ->setTitle(tr('User profile picture'))
               ->setContent(Img::new()
                               ->addClass('w100')
                               ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
//        ->setSrc($user->getPicture())
                               ->setAlt(tr('Profile picture for :user', [':user' => $user->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent(($user->isNew() ? '' : '<a href="' . UrlBuilder::getWww('/accounts/password+' . $user->getId() . '.html') . '">' . tr('Change password for this user') . '</a><br>') . '
                        <a href="' . UrlBuilder::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                        <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


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
                            // The user card and all additional cards
                                  ->addContent($user_card->render() .
                                               isset_get($roles_card)?->render() .
                                               isset_get($rights_card)?->render() .
                                               isset_get($emails_card)?->render() .
                                               isset_get($phones_card)?->render())
                                  ->setSize(9)
                                  ->useForm(true))
            ->addColumn($picture->render() . $relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setPageTitle(tr('User :user', [':user' => $user->getDisplayName()]));
Response::setHeaderTitle(tr('User'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           '/accounts/users.html' => tr('Users'),
                                                           '' => $user->getDisplayName(),
                                                       ]));
