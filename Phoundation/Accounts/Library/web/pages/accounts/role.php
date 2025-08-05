<?php

/**
 * Page accounts/roles.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Build the page content
$role = Role::new()->loadThis($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Validate rights
                $post = PostValidator::new()
                                     ->select('rights_id')->isOptional()->isArray()->forEachField()->isOptional()->isDbId()
                                     ->validate(false);

                // Update role and rights
                $role->apply()
                     ->save()
                     ->getRightsObject()
                         ->setRights($post['rights_id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Role ":role" has been saved', [':role' => $role->getName()]));
                Response::redirect('accounts/role+' . $role->getId() . '.html');

            case tr('Delete'):
                $role->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been deleted', [':role' => $role->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $role->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been undeleted', [':role' => $role->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $role->forceApply();
    }
}


// Audit button.
if ($role->isNotNew()) {
    $audit = Button::new()
                   ->setFloatRight(true)
                   ->setMode(EnumDisplayMode::information)
                   ->setAnchorUrl('/audit/meta+' . $role->getMetaId() . '.html')
                   ->setFloatRight(true)
                   ->setContent(tr('Audit'));

    if ($role->isDeleted()) {
        $delete = Button::new()
                        ->setFloatRight(true)
                        ->setMode(EnumDisplayMode::warning)
                        ->setOutlined(true)
                        ->setContent(tr('Undelete'));

    } else {
        $delete = Button::new()
                        ->setFloatRight(true)
                        ->setMode(EnumDisplayMode::warning)
                        ->setOutlined(true)
                        ->setContent(tr('Delete'));
    }

    $users = $role->getUsersObject();

    // Build the "users" list section
    $users_card = Card::new()
                      ->setTitle(tr('Users that have this role'))
                      ->setCollapseSwitch(true)
                      ->setMaximizeSwitch(true)
                      ->setContent($users->load()->getHtmlDataTableObject([
                                                      'id'            => tr('Id'),
                                                      'profile_image' => tr('Profile image'),
                                                      'email'         => tr('Email'),
                                                      'name'          => tr('Name'),
                                                      'roles'         => tr('Roles'),
                                                      'status'        => tr('Status'),
                                                      'sign_in_count' => tr('Signins'),
                                                      'created_on'    => tr('Created on'),
                                               ])->setRowUrl('/accounts/user+:ROW.html'));
}


// Build the role card
$role_card = Card::new()
                 ->setTitle(tr('Edit data for role :name', [':name' => $role->getName()]))
                 ->setCollapseSwitch(true)
                 ->setMaximizeSwitch(true)
                 ->setContent($role->getHtmlDataEntryFormObject())
                 ->useForm(true)
                 ->setButtons(Buttons::new()
                                     ->addButton(tr('Save'))
                                     ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/roles.html'), true)
                                     ->addButton(isset_get($delete))
                                     ->addButton(isset_get($audit)));


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new('/accounts/users.html' , tr('Manage users')) .
                                  AnchorBlock::new('/accounts/rights.html', tr('Manage rights')));


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build the "rights" list management section
$rights_card = Card::new()
                   ->setTitle(tr('Rights for this role'))
                   ->setCollapseSwitch(true)
                   ->setMaximizeSwitch(true)
                   ->setContent($role->getRightsHtmlDataEntryForm())
                   ->setForm(Form::new()
                                 ->setAction('#')
                                 ->setRequestMethod(EnumHttpRequestMethod::post))
                   ->render();


// Set page meta data
Response::setHeaderTitle(tr('Role'));
Response::setHeaderSubTitle($role->getDisplayName());
Response::setBreadcrumbs([
    Anchor::new('/'                   , tr('Home')),
    Anchor::new('/accounts.html'      , tr('Accounts')),
    Anchor::new('/accounts/roles.html', tr('Roles')),
    Anchor::new(''                    , $role->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($role_card     . $rights_card        . isset_get($users_card), EnumDisplaySize::nine)
           ->addGridColumn($relevant_card . $documentation_card                         , EnumDisplaySize::three);
