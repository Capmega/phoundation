<?php

/**
 * Page accounts/users.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\FilterForm;
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


// Build the filters card
$filters      = FilterForm::new();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Filters')
                    ->setContent($filters);


// Button clicked?
if (Request::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
                         ->select('accounts_users_length')->isOptional()->isNumeric()    // This is paging length, ignore
                         ->select('submit')->isOptional()->isVariable()
                         ->select('id')->isOptional()->isArray()->eachField()->isDbId()
                         ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected users
                $count = Users::directOperations()->deleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Deleted ":count" users', [':count' => $count]));
                Response::redirect('this');

            case tr('Undelete'):
                // Undelete selected users
                $count = Users::directOperations()->undeleteKeys($post['id']);

                Response::getFlashMessagesObject()->addSuccess(tr('Undeleted ":count" users', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Get the users list and apply filters
$users   = Users::new();
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

switch ($filters->get('status')) {
    case 'all':
        break;

    case null:
        $builder->addWhere('`accounts_users`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`accounts_users`.`status` = :status', [
            ':status' => $filters->get('status'),
        ]);
}

if ($filters->get('roles_id')) {
    $builder->addWhere('`accounts_users_roles`.`roles_id` = :roles_id', [
        ':roles_id' => $filters->get('roles_id'),
    ]);
}

if ($filters->get('rights_id')) {
    $builder->addJoin('JOIN `accounts_users_rights` 
                         ON `accounts_users_rights`.`rights_id` = :rights_id 
                        AND `accounts_users_rights`.`users_id`  = `accounts_users`.`id`', [
                            ':rights_id' => $filters->get('rights_id'),
    ]);
}


// Build users table
$buttons = Buttons::new()
                  ->addButton(tr('Create'), EnumDisplayMode::primary, '/accounts/user.html')
                  ->addButton(tr('Delete'), EnumDisplayMode::warning, EnumButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$users_card = Card::new()
                  ->setTitle('Active users')
                  ->setSwitches('reload')
                  ->setContent($users->load()
                                     ->getHtmlDataTableObject()
                                         ->setRowUrl('/accounts/user+:ROW.html'))
                  ->useForm(true)
                  ->setButtons($buttons);

$users_card->getForm()
           ->setAction(Url::getCurrent())
           ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                              <a href="' . Url::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Render and return the page grid
$grid = Grid::new()
            ->addGridColumn($filters_card->render() . $users_card->render(), EnumDisplaySize::nine)
            ->addGridColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Users'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Users'),
                                                       ]));
