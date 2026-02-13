<?php

/**
 * /accounts
 *
 * This is the main accounts menu page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// This page requires the "accounts" right
Request::requiresAllRights('accounts');


// This page allows no get parameters whatsoever
GetValidator::new()->validate();


// Set page meta-data
Response::setPageTitle(tr('Accounts portal'));
Response::setHeaderTitle(tr('Accounts portal'));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Accounts')),
]);


// Build link cards
$_card = Card::new()
            ->setTitle(tr('Accounts management'))
            ->setContent(AnchorBlock::new('/accounts/users.html'   , tr('Manage users')) .
                         AnchorBlock::new('/accounts/roles.html'   , tr('Manage roles')) .
                         AnchorBlock::new('/accounts/rights.html'  , tr('Manage rights')) .
                         hr(AnchorBlock::new('/accounts/sessions.html', tr('Manage sessions'))));


// Render and return the grid
return Grid::new()
           ->addGridColumn($_card, EnumDisplaySize::twelve);
