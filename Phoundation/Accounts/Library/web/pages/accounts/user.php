<?php

/**
 * Page accounts/user
 *
 * This is the primary user management page where we can manage all the basic information about a user account
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\LockButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\UnlockButton;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\UploadHandler;


// Validate GET arguments
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Get the requested user and modify form design
$_user = User::new()->loadThis($get['id']);
$_user->getDefinitionsObject()->setRenderMeta(!$_user->isNew())
                               ->setDefinitionRender('latitude'        , false)
                               ->setDefinitionRender('longitude'       , false)
                               ->setDefinitionRender('offset_latitude' , false)
                               ->setDefinitionRender('offset_longitude', false)
                               ->setDefinitionRender('accuracy'        , false)
                               ->setDefinitionRender('type'            , false)
                               ->setDefinitionRender('keywords'        , false)
                               ->setDefinitionRender('is_leader'       , false)
                               ->setDefinitionRender('priority'        , false)
                               ->setDefinitionRender('data'            , false);


// Users cannot modify themselves unless they have the "god" right
if (Session::getUserObject()->getId() === $get['id']) {
    if (!$_user->hasAllRights('god')) {
        $_user->setReadonly(true);
    }
}


if ($_user->isNotNew()) {
    // Define the drag/drop upload selector
    Request::getFileUploadHandlersObject()
           ->add(UploadHandler::new('image')
                              ->getDropZoneObject()
                              ->setUrlObject(Url::new('accounts/user/image/upload+' . $_user->getId())->makeAjax())
                              ->setSelector('#profile-picture-card')
                              ->setMaxFiles(0)
                              ->getHandlerObject())
           ->process();
}


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate roles
                $post = PostValidator::new()
                                     ->select('roles_id')->isOptional()->isArray()->forEachField()->isOptional()->isDbId()
                                     ->validate(false);

                // Update user, roles, emails, and phones
                $_user->apply(false);
                $_user->getEmailsObject()->apply(false);
                $_user->getPhonesObject()->apply();

                // TODO This method should also save all sub data like roles, emails, phones, etc...
                $_user->save();
                $_user->getRolesObject()->setRoles($post['roles_id']);
                $_user->getEmailsObject()->save();
                $_user->getPhonesObject()->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been saved', [
                    ':user' => $_user->getDisplayName(),
                ]));

                if ($_user->isCreated()) {
                    Response::getFlashMessagesObject()->addSuccess(tr('A welcome email has been sent to ":user"', [
                        ':user' => $_user->getDisplayName(),
                    ]));
                }

                // Redirect away from POST
                Response::redirect(Url::new('/accounts/user+' . $_user->getId() . '.html')->makeWww());

            case tr('Impersonate'):
                $_user->impersonate();

                Response::getFlashMessagesObject()->addSuccess(tr('You are now impersonating ":user"', [
                    ':user' => $_user->getDisplayName(),
                ]));

                Response::redirect('root');

            case tr('Delete'):
                $_user->delete();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been deleted', [
                    ':user' => $_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Lock'):
                $_user->lock();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been locked', [
                    ':user' => $_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Unlock'):
                $_user->unlock();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been unlocked', [
                    ':user' => $_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Undelete'):
                $_user->undelete();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been undeleted', [
                    ':user' => $_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Re-send welcome email'):
                if (!$_user->hasSignedIn()) {
                    $_user->sendWelcomeEmail();

                    Response::getFlashMessagesObject()->addSuccess(tr('Re-sent welcome email for user account ":user"', [
                        ':user' => $_user->getDisplayName(),
                    ]));

                } else {
                    Response::getFlashMessagesObject()->addWarning(tr('Cannot re-send welcome email for user account ":user", the user has already signed in', [
                        ':user' => $_user->getDisplayName(),
                    ]));
                }

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $_user->forceApply();
    }
}


// Save button
if (!$_user->getReadonly()) {
    $_button_save = Button::new()
                           ->setContent(tr('Save'))
                           ->setFloatRight(true);
}


// Impersonate button. We must have the right to impersonate, we cannot impersonate ourselves, and we cannot impersonate
// god users
if ($_user->canBeImpersonated()) {
    $_button_impersonate = Button::new()
                                  ->setFloatRight(true)
                                  ->setMode(EnumDisplayMode::danger)
                                  ->setContent(tr('Impersonate'))
                                  ->setFloatRight(true);
}


// Delete button. We cannot delete god users
if ($_user->canBeStatusChanged()) {
    if ($_user->isDeleted()) {
        $_button_delete = UndeleteButton::new();

    } else {
        $_button_delete = DeleteButton::new();

        if ($_user->isLocked()) {
            $_button_lock = UnlockButton::new();

        } else {
            $_button_lock = LockButton::new();
        }
    }
}


// Audit button.
if (!$_user->isNew()) {
    $_button_audit = AuditButton::new()->setUrlObject('/audit/meta+' . $_user->getMetaId() . '.html');
}


// Re-send welcome email button.
if (!$_user->hasSignedIn()) {
    $_button_welcome = Button::new()->setMode(EnumDisplayMode::primary)
                                     ->setOutlined(true)
                                     ->setContent(tr('Re-send welcome email'));
}


// Build the "user" form
$_user_card = Card::new()
                   ->setCollapseSwitch(true)
                   ->setMaximizeSwitch(true)
                   ->setTitle(tr('Edit profile for user :name', [':name' => $_user->getDisplayName()]))
                   ->setContent($_user->getHtmlFormObject())
                   ->setButtonsObject(Buttons::new()
                                             ->addButton(isset_get($_button_save))
                                             ->addBackButton(Url::newPrevious('/accounts/users.html'))
                                             ->addButton(isset_get($_button_audit))
                                             ->addButton(isset_get($_button_welcome))
                                             ->addButton(isset_get($_button_delete))
                                             ->addButton(isset_get($_button_lock))
                                             ->addButton(isset_get($_button_impersonate)));


// Build the additional cards only if we are not working on a new user
if (!$_user->isNew()) {
    $_roles_card = Card::new()
                        ->setCollapseSwitch(true)
                        ->setCollapsed(true)
                        ->setTitle(tr('Edit roles for this user (:count)', [':count' => $_user->getRolesObject()->getCount()]))
                        ->setContent($_user->getRolesHtmlDataEntryFormObject())
                        ->setButtonsObject(Buttons::new()
                                                  ->addButton(isset_get($_button_save))
                                                  ->addBackButton(Url::newPrevious('/accounts/users.html')));

    $_rights_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Rights for this user (:count)', [
                             ':count' => $_user->getRightsObject()->getCount()
                         ]))
                         ->setDescription(tr('This is a list of rights that this user has available because of its assigned roles. Each role gives the user a certain number of rights and with adding or removing roles, you add or remove these rights. These rights are used to determine the access to pages or specific information that a user has. To determine what rights are required to access a specific page, click the "lock" symbol at the top menu.'))
                         ->setContent($_user->getRightsObject(true, true)
                                             ->getHtmlDataTableObject('id,right,description')
                                                 ->setLengthChangeEnabled(false)
                                                 ->setSearchingEnabled(false)
                                                 ->setPagingEnabled(false)
                                                 ->setButtonsObject('copy,csv,excel,pdf,print')
                                                 ->setOrder([0 => 'asc'])
                                                 ->setOrderColumns([
                                                     0 => true,
                                                     1 => false,
                                                 ])
                                                 ->setInfoEnabled(false)
                                                 ->setCheckboxSelectors(EnumTableIdColumn::hidden));

    $_emails_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Additional email addresses for this user (:count)', [':count' => $_user->getEmailsObject()->getCount()]))
                         ->setContent($_user->getEmailsObject()->getHtmlDataEntryFormObject())
                         ->setButtonsObject(Buttons::new()
                                                   ->addButton(isset_get($_button_save))
                                                   ->addBackButton(Url::newPrevious('/accounts/users.html')));

    $_phones_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Additional phone numbers for this user (:count)', [':count' => $_user->getPhonesObject()->getCount()]))
                         ->setContent($_user->getPhonesObject()->getHtmlDataEntryFormObject())
                         ->setButtonsObject(Buttons::new()
                                                   ->addButton(isset_get($_button_save))
                                                   ->addBackButton(Url::newPrevious('/accounts/users.html')));
}


// Build profile picture card
$_picture_card = Card::new()
                      ->setTitle(tr('Users profile picture'))
                      ->setId('profile-picture-card')
                      ->setContent($_user->getProfileImageObject()->getHtmlImgObject()
                                                                   ->setId('profile-picture')
                                                                   ->addClasses('w100')
                                                                   ->setAlt(tr('My profile picture')));


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(($_user->isNew() ? AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Manage roles')) .
                                                        AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Manage rights'))
                                                      : AnchorBlock::new(Url::new('/profiles/profile+' . $_user->getId() . '.html')->makeWww(), tr('Profile page for this user')) .
                                                        AnchorBlock::new(Url::new('/accounts/password+' . $_user->getId() . '.html')->makeWww(), tr('Change password for this user')) .
                                                        AnchorBlock::new(Url::new('/reports/security/authentications.html')->makeWww()->addQueries('users_id=' . $_user->getId()), tr('Authentications for this user')) .
                                                        AnchorBlock::new(Url::new('/reports/security/incidents.html')->makeWww()->addQueries('users_id=' . $_user->getId()), tr('Security incidents for this user')) .
                                                        AnchorBlock::new(Url::new('/accounts/sessions.html')->makeWww()->addQueries('users_id=' . $_user->getId()), tr('Manage sessions for this user')) .
                                                        hr(AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Manage roles')) .
                                                           AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Manage rights')))));


// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                          <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                          <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta-data
Response::setPageTitle(tr('User :user', [':user' => $_user->getDisplayName()]));
Response::setHeaderTitle(tr('User'));
Response::setHeaderSubTitle($_user->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                   , tr('Home')),
    Breadcrumb::new('/accounts.html'      , tr('Accounts')),
    Breadcrumb::new('/accounts/users.html', tr('Users')),
    Breadcrumb::new(''                    , $_user->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
            ->addGridColumn(GridColumn::new()
                            // The user card and all additional cards
                                      ->addContent($_user_card .
                                                   isset_get($_roles_card) .
                                                   isset_get($_rights_card) .
                                                   isset_get($_emails_card) .
                                                   isset_get($_phones_card))
                                      ->setSize(9)
                                      ->useForm(true))
            ->addGridColumn($_picture_card . $_relevant_card . $_documentation_card, EnumDisplaySize::three);
