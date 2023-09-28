<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\FilterForm;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\ButtonType;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page accounts/users.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Build the page content
// Build users filter card
$filters      = FilterForm::new()->apply();
$filters_card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle('Filters')
    ->setContent($filters->render())
    ->useForm(true);


// Button clicked?
if (Page::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
        ->select('accounts_users_length')->isOptional()->isNumeric()    // This is paging length, ignore
        ->select('submit')->isOptional()->isVariable()
        ->select('id')->isOptional()->isArray()->each()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch ($post['submit']) {
            case tr('Delete'):
                // Delete selected users
                $count = Users::directOperations()->delete($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Deleted ":count" users', [':count' => $count]));
                Page::redirect('this');

            case tr('Undelete'):
                // Undelete selected users
                $count = Users::directOperations()->undelete($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Undeleted ":count" users', [':count' => $count]));
                Page::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Get users list and apply filters
$users   = Users::new();
$builder = $users->getQueryBuilder()
    ->addSelect('`accounts_users`.`id`, 
                 TRIM(CONCAT(`first_names`, " ", `last_names`)) AS `name`, 
                 GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`, 
                 `accounts_users`.`email`, 
                 `accounts_users`.`status`, 
                 `accounts_users`.`created_on`')
    ->addJoin('LEFT JOIN `accounts_users_roles`
               ON        `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
    ->addJoin('LEFT JOIN `accounts_roles`
               ON        `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`')
    ->addWhere('`accounts_users`.`email` != "guest"')
    ->addGroupBy('`accounts_users`.`id`');

switch ($filters->getSourceKey('entry_status')) {
    case '__all':
        break;

    case null:
        $builder->addWhere('`accounts_users`.`status` IS NULL');
        break;

    default:
        $builder->addWhere('`accounts_users`.`status` = :status', [':status' => $filters->getSourceKey('entry_status')]);
}

if ($filters->getSourceKey('roles_id')) {
    $builder->addWhere('`accounts_users_roles`.`roles_id` = :roles_id', [':roles_id' => $filters->getSourceKey('roles_id')]);
}

if ($filters->getSourceKey('rights_id')) {
    $builder->addJoin('JOIN `accounts_users_rights` ON `accounts_users_rights`.`rights_id` :rights_id AND `accounts_users_rights`.`users_id` = `accounts_users`.`id`', [':rights_id' => $filters->getSourceKey('rights_id')]);
}


// Build users table
$buttons = Buttons::new()
    ->addButton(tr('Create'), DisplayMode::primary, '/accounts/user.html')
    ->addButton(tr('Delete'), DisplayMode::warning, ButtonType::submit, true, true);

// TODO Automatically re-select items if possible
//    ->select($post['id']);

$users_card = Card::new()
    ->setTitle('Active users')
    ->setSwitches('reload')
    ->setContent($users
        ->load()
        ->getHtmlDataTable()
            ->setDateFormat('YYYY-MM-DD HH:mm:ss')
            ->setRowUrl('/accounts/user-:ROW.html'))
    ->useForm(true)
    ->setButtons($buttons);

$users_card->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters_card->render() . $users_card->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Users'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
