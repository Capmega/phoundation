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
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
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
$_role = Role::new()->loadThis($get['id']);


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
                $_role->apply()
                       ->save()
                       ->getRightsObject()
                           ->setRights($post['rights_id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Role ":role" has been saved', [':role' => $_role->getName()]));
                Response::redirect('accounts/role+' . $_role->getId() . '.html');

            case tr('Delete'):
                $_role->delete();

                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been deleted', [':role' => $_role->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $_role->undelete();

                Response::getFlashMessagesObject()->addSuccess(tr('The role ":role" has been undeleted', [':role' => $_role->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $_role->forceApply();
    }
}


// Audit button.
if ($_role->isNotNew()) {
    $_audit = Button::new()
                     ->setFloatRight(true)
                     ->setMode(EnumDisplayMode::information)
                     ->setUrlObject('/audit/meta+' . $_role->getMetaId() . '.html')
                     ->setFloatRight(true)
                     ->setContent(tr('Audit'));

    if ($_role->isDeleted()) {
        $_delete = Button::new()
                          ->setFloatRight(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setOutlined(true)
                          ->setContent(tr('Undelete'));

    } else {
        $_delete = DeleteButton::new();
    }

    $_users = $_role->getUsersObject();

    // Build the "users" list section
    $_users_card = Card::new()
                      ->setTitle(tr('Users that have this role (:count)', [':count' => $_users->getCount()]))
                      ->setCollapseSwitch(true)
                      ->setMaximizeSwitch(true)
                      ->setContent($_users->getHtmlDataTableObject([
                                               'id'            => tr('Id'),
                                               'profile_image' => tr('Profile image'),
                                               'email'         => tr('Email'),
                                               'name'          => tr('Name'),
                                               'roles'         => tr('Roles'),
                                               'status'        => tr('Status'),
                                               'sign_in_count' => tr('Signins'),
                                               'created_on'    => tr('Created on'),
                                           ])
                                           ->setRowUrls('/accounts/user+:ROW.html'));
}


// Build the role card
$_role_card = Card::new()
                   ->setTitle(tr('Edit data for role :name', [':name' => $_role->getName()]))
                   ->setCollapseSwitch(true)
                   ->setMaximizeSwitch(true)
                   ->setContent($_role->getHtmlDataEntryFormObject())
                   ->setButtonsObject(Buttons::new()
                                             ->addSaveButton()
                                             ->addBackButton(Url::newPrevious('/accounts/roles.html'), true)
                                             ->addButton(isset_get($_delete))
                                             ->addButton(isset_get($_audit)));


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new('/accounts/users.html' , tr('Manage users')) .
                                    AnchorBlock::new('/accounts/roles.html' , tr('Manage roles')) .
                                    AnchorBlock::new('/accounts/rights.html', tr('Manage rights')));


// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build the "rights" list management section
$_rights_card = Card::new()
                     ->setTitle(tr('Rights for this role'))
                     ->setCollapseSwitch(true)
                     ->setMaximizeSwitch(true)
                     ->setContent($_role->getRightsHtmlDataEntryForm());


// Set page meta-data
Response::setHeaderTitle(tr('Role'));
Response::setHeaderSubTitle($_role->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                   , tr('Home')),
    Breadcrumb::new('/accounts.html'      , tr('Accounts')),
    Breadcrumb::new('/accounts/roles.html', tr('Roles')),
    Breadcrumb::new(''                    , $_role->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn(GridColumn::new()
                                     // The role card and all additional cards
                                     ->addContent($_role_card . $_rights_card)
                                     ->setSize(9)
                                     ->useForm(true))
          ->addGridColumn($_relevant_card . $_documentation_card, EnumDisplaySize::three)
          ->addGridColumn(isset_get($_users_card)                , EnumDisplaySize::nine);
