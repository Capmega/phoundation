<?php

/**
 * Page phoundation/libraries
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Requests\Response;


// Set page meta-data
Response::setHeaderTitle(tr('Libraries'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'           , tr('Home')),
    Breadcrumb::new('/phoundation', tr('Phoundation')),
    Breadcrumb::new(''            , tr('Libraries')),
]);


// Build the page content
return Card::new()
    ->setContent(Libraries::new()->getHtmlDataTableObject());
