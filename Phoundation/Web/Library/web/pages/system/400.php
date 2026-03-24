<?php

/**
 * Page 422
 *
 * This is the page that will be shown when a user request cannot be processed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Web\Html\Pages\Page;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Get the exception
$e = Core::readRegister('e');


// Set page meta-data
Response::setHttpCode(422);
Response::setRenderMainWrapper(false);
Response::setPageTitle('422 - Unprocessable Content');
Response::setHeaderTitle(tr('422 - Unprocessable Content'));
Response::setDescription(tr('You sent incorrect or invalid information and your request was denied'));
Response::setBreadcrumbs();


// Render and return the system page
return Page::new('system/http-error')->addTextsObject([
    ':h2'     => '422',
    ':h3'     => tr('Unprocessable Content'),
    ':img'    => Url::new('backgrounds/404/large.jpg')->makeImg(),
    ':p'      => tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact the system administrator'),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::new('search/')->makeWww()
]);
