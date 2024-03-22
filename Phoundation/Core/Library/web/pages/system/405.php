<?php

/**
 * Page 405 - Method not allowed
 *
 * This is the page that will be shown when the requested HTTP method is not allowed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Requests\Request;

// Get the exception
$e = Core::readRegister('e');


// JSON type pages should not return this HTML
switch (Request::getRequestType()) {
    case EnumRequestTypes::ajax:
        // no break
    case EnumRequestTypes::api:
        Response::setHttpCode(405);
        Json::reply(['error' => tr('Method not allowed')]);
}


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '405',
    ':h3'     => tr('Method not allowed'),
    ':p'      => tr('The action you requested could not be executed because the method is (currently) not allowed. Please try again later or contact your system administrator', [
            ':url' => Request::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Response::setHttpCode(405);
Response::setBuildBody(false);
Response::setPageTitle('405 - Method not allowed');
Response::setHeaderTitle(tr('405 - Method not allowed'));
Response::setDescription(tr('The specified method is not allowed'));
Response::setBreadCrumbs();
