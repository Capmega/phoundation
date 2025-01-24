<?php

/**
 * Page accounts/users.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\FilterForm;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Build the "filters" card
$filters      = FilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Filters')
                    ->setContent($filters);


// Button clicked?
if (Request::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
                         ->ignoreFields('accounts_users_length') // This is paging length, ignore
                         ->select('id')->isOptional()->isArray()->eachField()->isDbId()
                         ->validate();

    try {
        // Process buttons
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Lock'):
                if ($post['id']) {
                    foreach ($post['id'] as $id) {
                        $user = User::new($id)->lock();

                        Response::getFlashMessagesObject()
                                ->addSuccess(tr('The user ":user" has been locked', [
                                    ':user' => $user->getName()
                                ]));
                    }

                    Response::redirect();
                }

                Response::getFlashMessagesObject()->addWarning(tr('No users selected to be locked'));
                Response::redirect();

            case tr('Delete'):
                if ($post['id']) {
                    foreach ($post['id'] as $id) {
                        $user = User::new($id)->delete();

                        Response::getFlashMessagesObject()
                                ->addSuccess(tr('The user ":user" has been deleted', [
                                    ':user' => $user->getName()
                                ]));
                    }

                    Response::redirect();
                }

                Response::getFlashMessagesObject()->addWarning(tr('No users selected to be deleted'));
                Response::redirect();
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Get the users list and apply filters
$users   = Users::new()->setFilterFormObject($filters);
$builder = $users->getQueryBuilder()
                 ->addSelect('
                     `accounts_users`.`id`, 
                     TRIM(CONCAT(`first_names`, " ", `last_names`)) AS `name`, 
                     `accounts_users`.`email`, 
                     `accounts_users`.`status`, 
                     GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`, 
                     `accounts_users`.`sign_in_count`,
                     `accounts_users`.`created_on`,
                     `accounts_users`.`profile_image`
                 ')
                 ->addJoin('LEFT JOIN `accounts_users_roles`
                            ON        `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
                 ->addJoin('LEFT JOIN `accounts_roles`
                            ON        `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`')
                 ->addWhere('`accounts_users`.`email` != "guest"')
                 ->addGroupBy('`accounts_users`.`id`');


// Build "users" table
$buttons = Buttons::new()
                  ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/user.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true)
                  ->addButton(tr('Lock')  , EnumDisplayMode::warning, EnumButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);
$users_card = Card::new()
                  ->setTitle('Active users')
                  ->setSwitches('reload')
                  ->setContent($users->load()
                                     ->getHtmlDataTableObject([
                                         'id'            => tr('Id'),
                                         'profile_image' => tr('Profile image'),
                                         'email'         => tr('Email'),
                                         'name'          => tr('Name'),
                                         'roles'         => tr('Roles'),
                                         'status'        => tr('Status'),
                                         'sign_in_count' => tr('Signins'),
                                         'created_on'    => tr('Created on'),
                                     ])->setRowUrl('/accounts/user+:ROW.html'))
                  ->useForm(true)
                  ->setButtons($buttons);


$users_card->getForm()
           ->setAction(Url::newCurrent())
           ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::new('/accounts/roles.html')->makeWww() . '">' . tr('Roles management') . '</a><br>
                                   <a href="' . Url::new('/accounts/rights.html')->makeWww() . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Users'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Users'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($filters_card  . $users_card        , EnumDisplaySize::nine)
           ->addGridColumn($relevant_card . $documentation_card, EnumDisplaySize::three);
