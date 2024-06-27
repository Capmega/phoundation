<?php

/**
 * Page 503
 *
 * This is the page that will be shown when the system is down for maintenance
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Pages\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Get the exception
$e = Core::readRegister('e');


// JSON type pages should not return this HTML
switch (Request::getRequestType()) {
    case EnumRequestTypes::ajax:
        // no break
    case EnumRequestTypes::api:
        Response::setHttpCode(503);
        Json::reply(['error' => tr('Service Unavailable')]);
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '503',
                                                       ':h3'     => tr('Service Unavailable'),
                                                       ':img'    => UrlBuilder::getImg('backgrounds/medinet-mobile/404/large.jpg'),
                                                       ':p'      => tr('The server is under maintenance and will return momentarily. Please contact the system administrator for more information'),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':action' => UrlBuilder::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(503);
Response::setBuildBody(false);
Response::setPageTitle('503 - Service Unavailable');
Response::setHeaderTitle(tr('503 - Error'));
Response::setDescription(tr('The server is under maintenance and will return momentarily'));
Response::setBreadCrumbs();
