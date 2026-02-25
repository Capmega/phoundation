<?php

/**
 * Page audit/meta
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Meta\MetaList;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
                   ->select('id')->sanitizeForceArray('-')->forEachField()->isDbId()
                   ->validate();


$_meta = MetaList::new($get['id'])->getHtmlDataTableObject();
$_card = Card::new()
              ->setTitle(tr('Registered activities for this object (:count)', [':count' => $_meta->getCount()]))
              ->setSwitches('reload,maximize')
              ->setContent($_meta);


// Build documentation
$_documentation = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Documentation'))
                       ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta-data
Response::setHeaderTitle(tr('Audit information'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'          , tr('Home')),
    Breadcrumb::new('/audit.html', tr('Audits')),
    Breadcrumb::new(''           , Strings::truncate(Strings::force($get['id'], ', '), 32)),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($_card         , EnumDisplaySize::nine)
           ->addGridColumn($_documentation, EnumDisplaySize::three);
