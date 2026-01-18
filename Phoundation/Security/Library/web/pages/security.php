<?php

/**
 * /security
 *
 * This is the main security menu page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
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


// This page requires the "security" right
Request::requiresAllRights('security');


// This page allows no get parameters whatsoever
GetValidator::new()->validate();


// Set page meta-data
Response::setPageTitle(tr('Security portal'));
Response::setHeaderTitle(tr('Security portal'));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Security')),
]);


// Build link cards
$o_card = Card::new()
            ->setTitle(tr('Security management'))
            ->setContent(AnchorBlock::new('/reports/security/authentications.html', tr('Authentications management')) .
                         AnchorBlock::new('/reports/security/incidents.html', tr('Incidents management')));


// Render and return the grid
return Grid::new()
           ->addGridColumn($o_card, EnumDisplaySize::twelve);
