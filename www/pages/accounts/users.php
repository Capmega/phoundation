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


// Get users list
$users = Users::new();


// Button clicked?
if (Page::isPostRequestMethod()) {
    // Validate POST
    $post = PostValidator::new()
        ->select('id')->isOptional()->isArray()->each()->isDbId()
        ->validate();

    try {
        // Process buttons
        switch (PostValidator::getSubmitButton()) {
            case tr('Delete'):
                // Delete selected users
                $count = $users->delete($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Deleted ":count" users', [':count' => $count]));
                Page::redirect('this');
                break;

            case tr('Undelete'):
                // Undelete selected users
                $count = $users->undelete($post['id']);

                Page::getFlashMessages()->addSuccessMessage(tr('Undeleted ":count" users', [':count' => $count]));
                Page::redirect('this');
                break;
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Build the page content
// Build users filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle('Users filters')
    ->setContent($filters_content->render())
    ->useForm(true);


// Build users table

$buttons = Buttons::new()
    ->addButton(tr('Create'), DisplayMode::primary, '/accounts/user.html')
    ->addButton(tr('Delete'), DisplayMode::warning, ButtonType::submit, true, true);

$table = $users->getHtmlDataTable()
    ->setRowUrl('/accounts/user-:ROW.html');
// TODO Automatically re-select items if possible
//    ->select($post['id']);

$users = Card::new()
    ->setTitle('Active users')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true)
    ->setButtons($buttons);

$users->getForm()
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
    ->addColumn($filters->render() . $users->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Users'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Users')
]));
