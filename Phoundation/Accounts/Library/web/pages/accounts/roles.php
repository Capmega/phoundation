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

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\FilterForm;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// This page does not accept GET parameters
GetValidator::new()->validate();


// Build filter card
$o_filters = FilterForm::new();
$o_filters->getDefinitionsObject()->setDefinitionRender('roles_id', false)
                                  ->setDefinitionSize('rights_id' , 6)
                                  ->setDefinitionSize('status'    , 6);

$o_filters_card = Card::new()
               ->setCollapseSwitch(true)
               ->setTitle('Filters')
               ->setContent($o_filters);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        $post = PostValidator::new()
                             ->ignoreFields('accounts_roles_length')
                             ->select('id')->isOptional()->isArray()->forEachField()->isDbId()
                             ->validate();

        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Delete'):
                if ($post['id']) {
                    foreach ($post['id'] as $id) {
                        $o_role = Role::new($id)->delete();

                        Response::getFlashMessagesObject()
                                ->addSuccess(tr('The role ":role" has been deleted', [
                                    ':role' => $o_role->getName()
                                ]));
                    }

                    Response::redirect();
                }

                Response::getFlashMessagesObject()->addWarning(tr('No roles selected to be deleted'));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Load the "roles" object
$o_roles = Roles::new()->load();


// Build "roles" card
$o_roles_card = Card::new()
                    ->setTitle(tr('Active roles (:count)', [':count' => $o_roles->getCount()]))
                    ->setSwitches('reload')
                    ->setContent($o_roles->setFilterFormObject($o_filters)
                                         ->getHtmlDataTableObject([
                                             'id'          => tr('Id'),
                                             'role'        => tr('Role'),
                                             'rights'      => tr('Gives user rights'),
                                             'description' => tr('Description'),
                                         ])
                                         ->setRowUrl('/accounts/role+:ROW.html')
                                         ->setTopButtons(Buttons::new()
                                                                ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/role.html')))
                  ->useForm(true)
                  ->setButtonsObject(Buttons::new()
                                            ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/role.html')
                                            ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new('/accounts/users.html'   , tr('Manage users')) .
                                    AnchorBlock::new('/accounts/rights.html'  , tr('Manage rights')) .
                                    AnchorBlock::new('/accounts/sessions.html', tr('Manage sessions')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Roles'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/accounts.html', tr('Accounts')),
    Breadcrumb::new(''              , tr('Roles')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card . $o_roles_card        , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
