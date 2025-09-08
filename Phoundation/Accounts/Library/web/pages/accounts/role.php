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
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Build the page content
$o_role = Role::new()->loadThis($get['id']);


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
                $o_role->apply()
                       ->save()
                       ->getRightsObject()
                           ->setRights($post['rights_id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Role ":role" has been saved', [':role' => $o_role->getName()]));
                Response::redirect('accounts/role+' . $o_role->getId() . '.html');

            case tr('Delete'):
                $o_role->delete();

                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been deleted', [':role' => $o_role->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $o_role->undelete();

                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been undeleted', [':role' => $o_role->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $o_role->forceApply();
    }
}


// Audit button.
if ($o_role->isNotNew()) {
    $o_audit = Button::new()
                     ->setFloatRight(true)
                     ->setMode(EnumDisplayMode::information)
                     ->setAnchorUrl('/audit/meta+' . $o_role->getMetaId() . '.html')
                     ->setFloatRight(true)
                     ->setContent(tr('Audit'));

    if ($o_role->isDeleted()) {
        $o_delete = Button::new()
                          ->setFloatRight(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setOutlined(true)
                          ->setContent(tr('Undelete'));

    } else {
        $o_delete = Button::new()
                          ->setFloatRight(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setOutlined(true)
                          ->setContent(tr('Delete'));
    }

    $o_users = $o_role->getUsersObject();

    // Build the "users" list section
    $o_users_card = Card::new()
                      ->setTitle(tr('Users that have this role (:count)', [':count' => $o_users->getCount()]))
                      ->setCollapseSwitch(true)
                      ->setMaximizeSwitch(true)
                      ->setContent($o_users->getHtmlDataTableObject([
                                               'id'            => tr('Id'),
                                               'profile_image' => tr('Profile image'),
                                               'email'         => tr('Email'),
                                               'name'          => tr('Name'),
                                               'roles'         => tr('Roles'),
                                               'status'        => tr('Status'),
                                               'sign_in_count' => tr('Signins'),
                                               'created_on'    => tr('Created on'),
                                           ])
                                           ->setRowUrl('/accounts/user+:ROW.html'));
}


// Build the role card
$o_role_card = Card::new()
                   ->setTitle(tr('Edit data for role :name', [':name' => $o_role->getName()]))
                   ->setCollapseSwitch(true)
                   ->setMaximizeSwitch(true)
                   ->setContent($o_role->getHtmlDataEntryFormObject())
                   ->setButtonsObject(Buttons::new()
                                             ->addButton(tr('Save'))
                                             ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/roles.html'), true)
                                             ->addButton(isset_get($o_delete))
                                             ->addButton(isset_get($o_audit)));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new('/accounts/users.html' , tr('Manage users')) .
                                    AnchorBlock::new('/accounts/roles.html' , tr('Manage roles')) .
                                    AnchorBlock::new('/accounts/rights.html', tr('Manage rights')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build the "rights" list management section
$o_rights_card = Card::new()
                     ->setTitle(tr('Rights for this role'))
                     ->setCollapseSwitch(true)
                     ->setMaximizeSwitch(true)
                     ->setContent($o_role->getRightsHtmlDataEntryForm());


// Set page meta data
Response::setHeaderTitle(tr('Role'));
Response::setHeaderSubTitle($o_role->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                   , tr('Home')),
    Breadcrumb::new('/accounts.html'      , tr('Accounts')),
    Breadcrumb::new('/accounts/roles.html', tr('Roles')),
    Breadcrumb::new(''                    , $o_role->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn(GridColumn::new()
                                     // The role card and all additional cards
                                     ->addContent($o_role_card . $o_rights_card)
                                     ->setSize(9)
                                     ->useForm(true))
           ->addGridColumn(isset_get($o_users_card), EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card                           , EnumDisplaySize::three);
