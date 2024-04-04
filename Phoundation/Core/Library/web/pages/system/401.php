<?php

/**
 * Page 401
 *
 * This page will be executed when a guest user accesses a resource that requires authentication first. The sign-in page
 * will be shown with an HTTP 401 error code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

Response::setHttpCode(401);
Request::execute('pages/system/401');

//// Get the exception
//$e = Core::readRegister('e');
//
//
//// JSON type pages should not return this HTML
//switch (Request::getRequestType()) {
//    case EnumRequestTypes::ajax:
//        // no break
//    case EnumRequestTypes::api:
//        Response::setHttpCode(401);
//        Json::reply(['error' => tr('Authentication required')]);
//}
//
//
//// Build the error page
//echo Template::page('system/http-error')->render([
//    ':h2'     => '401',
//    ':h3'     => tr('Unauthorized'),
//    ':p'      => tr('You need to login to access the specified resource. Please contact the system administrator if you think this was in error'),
//    ':type'   => 'warning',
//    ':search' => tr('Search'),
//    ':action' => UrlBuilder::getWww('search/')
//]);
//
//
//// Set page meta data
//Response::setHttpCode(401);
//Response::setBuildBody(false);
//Response::setPageTitle('401 - Unauthorized');
//Response::setHeaderTitle(tr('401 - Error'));
//Response::setDescription(tr('You need to login to access the specified resource'));
//Response::setBreadCrumbs();
