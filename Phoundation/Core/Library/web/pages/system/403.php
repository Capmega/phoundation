<?php

/**
 * Page 403
 *
 * This is the page that will be shown when a users access to a certain resource was prohibited
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
        Response::setHttpCode(403);
        Json::reply(['error' => tr('Forbidden')]);
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '403',
                                                       ':h3'     => tr('Forbidden'),
                                                       ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error'),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':action' => UrlBuilder::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(403);
Response::setBuildBody(false);
Response::setPageTitle('403 - Forbidden');
Response::setHeaderTitle(tr('403 - Error'));
Response::setDescription(tr('You do not have access to the specified resource'));
Response::setBreadCrumbs();
