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

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
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
$o_user = User::new()->loadThis($get['id']);
$o_user->getDefinitionsObject()->setRenderMeta(!$o_user->isNew())
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


if ($o_user->isNotNew()) {
    // Define the drag/drop upload selector
    Request::getFileUploadHandlersObject()
           ->add(UploadHandler::new('image')
                              ->getDropZoneObject()
                              ->setUrl(Url::new('accounts/user/image/upload+' . $o_user->getId())->makeAjax())
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
                $o_user->apply(false);
                $o_user->getEmailsObject()->apply(false);
                $o_user->getPhonesObject()->apply();

                // TODO This method should also save all sub data like roles, emails, phones, etc...
                $o_user->save();
                $o_user->getRolesObject()->setRoles($post['roles_id']);
                $o_user->getEmailsObject()->save();
                $o_user->getPhonesObject()->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been saved', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                if ($o_user->isCreated()) {
                    Response::getFlashMessagesObject()->addSuccess(tr('A welcome email has been sent to ":user"', [
                        ':user' => $o_user->getDisplayName(),
                    ]));
                }

                // Redirect away from POST
                Response::redirect(Url::new('/accounts/user+' . $o_user->getId() . '.html')->makeWww());

            case tr('Impersonate'):
                $o_user->impersonate();

                Response::getFlashMessagesObject()->addSuccess(tr('You are now impersonating ":user"', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                Response::redirect('root');

            case tr('Delete'):
                $o_user->delete();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been deleted', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Lock'):
                $o_user->lock();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been locked', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Unlock'):
                $o_user->unlock();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been unlocked', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Undelete'):
                $o_user->undelete();

                Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been undeleted', [
                    ':user' => $o_user->getDisplayName(),
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $o_user->forceApply();
    }
}


// Save button
if (!$o_user->getReadonly()) {
    $o_button_save = Button::new()
                           ->setContent(tr('Save'))
                           ->setFloatRight(true);
}


// Impersonate button. We must have the right to impersonate, we cannot impersonate ourselves, and we cannot impersonate
// god users
if ($o_user->canBeImpersonated()) {
    $o_button_impersonate = Button::new()
                                  ->setFloatRight(true)
                                  ->setMode(EnumDisplayMode::danger)
                                  ->setContent(tr('Impersonate'))
                                  ->setFloatRight(true);
}


// Delete button. We cannot delete god users
if ($o_user->canBeStatusChanged()) {
    if ($o_user->isDeleted()) {
        $o_button_delete = Button::new()
                                 ->setFloatRight(true)
                                 ->setMode(EnumDisplayMode::warning)
                                 ->setOutlined(true)
                                 ->setContent(tr('Undelete'))
                                 ->setFloatRight(true);

    } else {
        $o_button_delete = Button::new()
                                 ->setFloatRight(true)
                                 ->setMode(EnumDisplayMode::warning)
                                 ->setOutlined(true)
                                 ->setContent(tr('Delete'))
                                 ->setFloatRight(true);

        if ($o_user->isLocked()) {
            $o_button_lock = Button::new()
                                   ->setFloatRight(true)
                                   ->setMode(EnumDisplayMode::warning)
                                   ->setContent(tr('Unlock'))
                                   ->setFloatRight(true);

        } else {
            $o_button_lock = Button::new()
                                   ->setFloatRight(true)
                                   ->setMode(EnumDisplayMode::warning)
                                   ->setContent(tr('Lock'))
                                   ->setFloatRight(true);
        }
    }
}


// Audit button.
if (!$o_user->isNew()) {
    $o_button_audit = Button::new()
                            ->setFloatRight(true)
                            ->setMode(EnumDisplayMode::information)
                            ->setAnchorUrl('/audit/meta+' . $o_user->getMetaId() . '.html')
                            ->setFloatRight(true)
                            ->setContent(tr('Audit'))
                            ->setFloatRight(true);
}


// Build the "user" form
$o_user_card = Card::new()
                   ->setCollapseSwitch(true)
                   ->setMaximizeSwitch(true)
                   ->setTitle(tr('Edit profile for user :name', [':name' => $o_user->getDisplayName()]))
                   ->setContent($o_user->getHtmlDataEntryFormObject())
                   ->setButtons(Buttons::new()
                                       ->addButton(isset_get($o_button_save))
                                       ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/users.html'), true)
                                       ->addButton(isset_get($o_button_audit))
                                       ->addButton(isset_get($o_button_delete))
                                       ->addButton(isset_get($o_button_lock))
                                       ->addButton(isset_get($o_button_impersonate)));


// Build the additional cards only if we're not working on a new user
if (!$o_user->isNew()) {
    $o_roles_card = Card::new()
                        ->setCollapseSwitch(true)
                        ->setCollapsed(true)
                        ->setTitle(tr('Edit roles for this user (:count)', [':count' => $o_user->getRolesObject()->getCount()]))
                        ->setContent($o_user->getRolesHtmlDataEntryFormObject())
                        ->setButtons(Buttons::new()
                                            ->addButton(tr('Save'))
                                            ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/users.html'), true));

    $o_rights_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Rights for this user (:count)', [
                             ':count' => $o_user->getRightsObject()->getCount()
                         ]))
                         ->setDescription(tr('This is a list of rights that this user has available because of its assigned roles. Each role gives the user a certain number of rights and with adding or removing roles, you add or remove these rights. These rights are used to determine the access to pages or specific information that a user has. To determine what rights are required to access a specific page, click the "lock" symbol at the top menu.'))
                         ->setContent($o_user->getRightsObject(true, true)
                                             ->getHtmlDataTableObject('id,right,description')
                                                 ->setLengthChangeEnabled(false)
                                                 ->setSearchingEnabled(false)
                                                 ->setPagingEnabled(false)
                                                 ->setButtons('copy,csv,excel,pdf,print')
                                                 ->setOrder([0 => 'asc'])
                                                 ->setOrderColumns([
                                                     0 => true,
                                                     1 => false,
                                                 ])
                                                 ->setInfoEnabled(false)
                                                 ->setCheckboxSelectors(EnumTableIdColumn::hidden));

    $o_emails_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Additional email addresses for this user (:count)', [':count' => $o_user->getEmailsObject()->getCount()]))
                         ->setContent($o_user->getEmailsObject()->getHtmlDataEntryFormObject())
                         ->setButtons(Buttons::new()
                                             ->addButton(tr('Save'))
                                             ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/users.html'), true));

    $o_phones_card = Card::new()
                         ->setCollapseSwitch(true)
                         ->setCollapsed(true)
                         ->setTitle(tr('Additional phone numbers for this user (:count)', [':count' => $o_user->getPhonesObject()->getCount()]))
                         ->setContent($o_user->getPhonesObject()->getHtmlDataEntryFormObject())
                         ->setButtons(Buttons::new()
                                             ->addButton(tr('Save'))
                                             ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/users.html'), true));
}


// Build profile picture card
$o_picture_card = Card::new()
                      ->setTitle(tr('Users profile picture'))
                      ->setId('profile-picture-card')
                      ->setContent($o_user->getProfileImageObject()
                                        ->getHtmlImgObject()
                                            ->setId('profile-picture')
                                            ->addClasses('w100')
                                            ->setAlt(tr('My profile picture')));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(($o_user->isNew() ? '' : AnchorBlock::new(Url::new('/profiles/profile+' . $o_user->getId() . '.html')->makeWww(), tr('Profile page for this user'))) .
                                                             AnchorBlock::new(Url::new('/accounts/password+' . $o_user->getId() . '.html')->makeWww(), tr('Change password for this user')) .
                                                             AnchorBlock::new(Url::new('/security/authentications.html')->makeWww()->addQueries('users_id=' . $o_user->getId()), tr('Authentications for this user')) .
                                                             AnchorBlock::new(Url::new('/security/incidents.html')->makeWww()->addQueries('users_id=' . $o_user->getId()), tr('Security incidents for this user')) .
                                                             hr(AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Manage roles')) .
                                                                AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Manage rights')) .
                                                                AnchorBlock::new(Url::new('/accounts/sessions.html')->makeWww()->addQueries('users_id=' . $o_user->getId()), tr('Manage sessions'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                          <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                          <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta data
Response::setPageTitle(tr('User :user', [':user' => $o_user->getDisplayName()]));
Response::setHeaderTitle(tr('User'));
Response::setHeaderSubTitle($o_user->getDisplayName());
Response::setBreadcrumbs([
    Anchor::new('/'                   , tr('Home')),
    Anchor::new('/accounts.html'      , tr('Accounts')),
    Anchor::new('/accounts/users.html', tr('Users')),
    Anchor::new(''                    , $o_user->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
            ->addGridColumn(GridColumn::new()
                            // The user card and all additional cards
                                  ->addContent($o_user_card .
                                               isset_get($o_roles_card) .
                                               isset_get($o_rights_card) .
                                               isset_get($o_emails_card) .
                                               isset_get($o_phones_card))
                                  ->setSize(9)
                                  ->useForm(true))
            ->addGridColumn($o_picture_card . $o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
