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
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
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
$_right = Right::new()->loadThis($get['id']);
$_card  = $_right->getHtmlCardObject(Url::new('accounts/rights.html'))
                 ->handleButtonEvents();


// Build the "users" list section if this right already existed in the database (meaning that some users might already use it, display those here)
if (!$_right->isNew()) {
    $_users_card = Card::new()
                        ->setTitle(tr('Users that have this right'))
                        ->setCollapseSwitch(true)
                        ->setMaximizeSwitch(true)
                        ->setContent($_right->getUsersObject()
                                            ->load()->getHtmlDataTableObject([
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


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new('/accounts/users.html', tr('Manage users')) .
                                    AnchorBlock::new('/accounts/roles.html', tr('Manage roles')));


// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta-data
Response::setHeaderTitle(tr('Right'));
Response::setHeaderSubTitle($_right->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'                    , tr('Home')),
    Breadcrumb::new('/accounts.html'       , tr('Accounts')),
    Breadcrumb::new('/accounts/rights.html', tr('Rights')),
    Breadcrumb::new(''                     , $_right->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($_card          . isset_get($_users_card), EnumDisplaySize::nine)
           ->addGridColumn($_relevant_card . $_documentation_card   , EnumDisplaySize::three);
