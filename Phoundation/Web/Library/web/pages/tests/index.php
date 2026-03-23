<?php

/**
 * Page tests/index.html
 *
 * This is the main tests page containing links to a wide variety of test pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// No get parameters allowed
GetValidator::new()->validate();


// Set page meta-data
Response::setPageTitle(tr('Tests'));
Response::setHeaderTitle(tr('Tests'));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/'          , tr('Home')),
    Breadcrumb::new('/tests.html', tr('Tests')),
]);


// Build link cards
$_colors_card = Card::new()
                    ->setTitle(tr('Color tests'))
                    ->setContent(AnchorBlock::new('tests/white.html', tr('White background test')) .
                                 AnchorBlock::new('tests/black.html', tr('Black background test')) .
                                 AnchorBlock::new('tests/red.html'  , tr('Red background test')) .
                                 AnchorBlock::new('tests/green.html', tr('Green background test')) .
                                 AnchorBlock::new('tests/blue.html' , tr('Blue background test')));


// Render and return the grid
return Grid::new()
           ->addGridColumn($_colors_card, EnumDisplaySize::five);
