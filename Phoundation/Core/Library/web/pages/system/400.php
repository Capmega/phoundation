<?php

/**
 * Page 400
 *
 * This is the page that will be shown when a user sent incorrect information (typically caused by a non caught
 * validation exception)
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
        Response::setHttpCode(400);
        Json::reply($e->getData());
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '400',
                                                       ':h3'     => tr('Bad Request'),
                                                       ':p'      => tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact the system administrator'),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':action' => UrlBuilder::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(400);
Response::setBuildBody(false);
Response::setPageTitle('400 - Bad Request');
Response::setHeaderTitle(tr('400 - Error'));
Response::setDescription(tr('You sent incorrect or invalid information and your request was denied'));
Response::setBreadCrumbs();
