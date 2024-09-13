<?php

/**
 * Page audit/meta
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Meta\MetaList;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
                   ->select('id')->sanitizeForceArray('-')->each()->isDbId()
                   ->validate();


$meta  = MetaList::new($get['id']);
$table = $meta->getHtmlDataTableObject();
$card  = Card::new()
             ->setTitle('Registered activities')
             ->setSwitches('reload,maximize')
             ->setContent($table->render());


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
            ->addGridColumn($card->render(), EnumDisplaySize::nine)
            ->addGridColumn($documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Audit information'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'           => tr('Home'),
                                                           '/audit.html' => tr('Audits'),
                                                           ''            => tr('Item'),
                                                       ]));
