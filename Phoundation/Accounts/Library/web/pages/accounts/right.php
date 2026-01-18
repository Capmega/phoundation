<?php

/**
 * Page accounts/right.php
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
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Build the page content
$o_right = Right::new()
                ->loadThis($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Update right
                $o_right->apply()
                        ->save();

// TODO Implement timers
//showdie(Timers::get('query'));

                Response::getFlashMessagesObject()->addSuccess(tr('Right ":right" has been saved', [':right' => $o_right->getName()]));
                Response::redirect(Url::new('/accounts/right+' . $o_right->getId() . '.html')->makeWww());

            case tr('Delete'):
                $o_right->delete();

                Response::getFlashMessagesObject()->addSuccess(tr('The right ":right" has been deleted', [':right' => $o_right->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $o_right->undelete();

                Response::getFlashMessagesObject()->addSuccess(tr('The right ":right" has been undeleted', [':right' => $o_right->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
        Response::getFlashMessagesObject()->addMessage($e);
        $o_right->forceApply();
    }
}


// Audit button.
if (!$o_right->isNew()) {
    $o_audit = Button::new()
                     ->setFloatRight(true)
                     ->setMode(EnumDisplayMode::information)
                     ->setUrlObject('/audit/meta+' . $o_right->getMetaId() . '.html')
                     ->setFloatRight(true)
                     ->setContent(tr('Audit'));

    if ($o_right->isDeleted()) {
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

    $o_users = $o_right->getUsersObject();
// :TODO: Fix Users class first, make sure that Users::load() uses query builder instead of direct queries!
//    $o_users->getQueryBuilderObject()->addSelect('        `accounts_users`.`id`,
//                                                  TRIM(CONCAT(`first_names`, " ", `last_names`)) AS `name`,
//                                                  `accounts_users`.`email`,
//                                                  `accounts_users`.`status`,
//                                                  GROUP_CONCAT(CONCAT(UPPER(LEFT(`accounts_roles`.`name`, 1)), SUBSTRING(`accounts_roles`.`name`, 2)) SEPARATOR ", ") AS `roles`,
//                                                  `accounts_users`.`sign_in_count`,
//                                                  `accounts_users`.`created_on`,
//                                                  `accounts_users`.`profile_image`')
//                             ->addJoin('LEFT JOIN `accounts_users_roles`
//                                               ON `accounts_users_roles`.`users_id` = `accounts_users`.`id`')
//                             ->addJoin('LEFT JOIN `accounts_roles`
//                                               ON `accounts_roles`.`id` = `accounts_users_roles`.`roles_id`')
//                             ->addWhere('         `accounts_users`.`email` != "guest"')
//                             ->addGroupBy('       `accounts_users`.`id`');

    // Build the "users" list section
    $o_users_card = Card::new()
                        ->setTitle(tr('Users that have this right'))
                        ->setCollapseSwitch(true)
                        ->setMaximizeSwitch(true)
                        ->setContent($o_users->load()->getHtmlDataTableObject([
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


// Build the right card
$o_card = Card::new()
              ->setTitle(tr('Edit data for right :name', [':name' => $o_right->getName()]))
              ->setCollapseSwitch(true)
              ->setMaximizeSwitch(true)
              ->setContent($o_right->getHtmlDataEntryFormObject())
              ->useForm(true)
              ->setButtonsObject(Buttons::new()
                                        ->addButton(tr('Save'))
                                        ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/rights.html'), true)
                                        ->addButton(isset_get($o_delete))
                                        ->addButton(isset_get($o_audit)));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new('/accounts/users.html', tr('Manage users')) .
                                    AnchorBlock::new('/accounts/roles.html', tr('Manage roles')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta-data
Response::setHeaderTitle(tr('Right'));
Response::setHeaderSubTitle($o_right->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                    , tr('Home')),
    Breadcrumb::new('/accounts.html'       , tr('Accounts')),
    Breadcrumb::new('/accounts/rights.html', tr('Rights')),
    Breadcrumb::new(''                     , $o_right->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_card          . isset_get($o_users_card), EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card   , EnumDisplaySize::three);
