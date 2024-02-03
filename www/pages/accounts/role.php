<?php

declare(strict_types=1);

use Phoundation\Accounts\Roles\Role;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Form;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page accounts/roles.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Validate
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();


// Build the page content
$role = Role::get($get['id'], no_identifier_exception: false);


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Save'):
                // Validate rights
                $post = PostValidator::new()
                    ->select('rights_id')->isOptional()->isArray()->each()->isOptional()->isDbId()
                    ->validate(false);

                // Update role and rights
                $role
                    ->apply()
                    ->save()
                    ->getRights()
                    ->setRights($post['rights_id']);

// TODO Implement timers
//showdie(Timers::get('query'));

                Page::getFlashMessages()->addSuccessMessage(tr('Role ":role" has been saved', [':role' => $role->getName()]));
                Page::redirect('referer');

            case tr('Delete'):
                $role->delete();
                Page::getFlashMessages()->addSuccessMessage(tr('The role ":role" has been deleted', [':role' => $role->getName()]));
                Page::redirect();

            case tr('Undelete'):
                $role->undelete();
                Page::getFlashMessages()->addSuccessMessage(tr('The role ":role" has been undeleted', [':role' => $role->getName()]));
                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
        $role->forceApply();
    }
}


// Audit button.
if (!$role->isNew()) {
    $audit = Button::new()
        ->setFloatRight(true)
        ->setMode(DisplayMode::information)
        ->setAnchorUrl('/audit/meta+' . $role->getMetaId() . '.html')
        ->setFloatRight(true)
        ->setValue(tr('Audit'))
        ->setContent(tr('Audit'));

    $delete = Button::new()
        ->setFloatRight(true)
        ->setMode(DisplayMode::warning)
        ->setOutlined(true)
        ->setValue(tr('Delete'))
        ->setContent(tr('Delete'));
}


// Build the role card
$form = $role->getHtmlDataEntryForm();
$card = Card::new()
    ->setTitle(tr('Edit data for role :name', [':name' => $role->getName()]))
    ->setContent($form->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Save'))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/accounts/roles.html'), true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($audit)));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build the rights list management section
$rights = Card::new()
    ->setTitle(tr('Rights for this role'))
    ->setContent($role->getRightsHtmlDataEntryForm())
    ->setForm(Form::new()
        ->setAction('#')
        ->setMethod('POST'))
    ->render();


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($card . $rights, DisplaySize::nine, true)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Role'));
Page::setHeaderSubTitle($role->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/accounts/roles.html' => tr('Roles'),
    ''                     => $role->getName()
]));
