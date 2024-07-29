<?php

/**
 * Page 409 - Conflict
 *
 * This is the page that will be shown when the specified could not be completed due to a conflict with the current
 * state of the target resource
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
use Phoundation\Web\Http\Url;
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
        Response::setHttpCode(409);
        Json::reply(['error' => tr('Conflict')]);
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '409',
                                                       ':h3'     => tr('Conflict'),
                                                       ':img'    => Url::getImg('backgrounds/medinet-mobile/404/large.jpg'),
                                                       ':p'      => tr('The specified could not be completed due to a conflict with the current state of the target resource.', [
                                                           ':url' => Request::getReferer(true),
                                                       ]),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':action' => Url::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(409);
Response::setRenderMainWrapper(false);
Response::setPageTitle('409 - Conflict');
Response::setHeaderTitle(tr('409 - Conflict'));
Response::setDescription(tr('The specified could not be completed due to a conflict with the current state of the target resource'));
Response::setBreadCrumbs();
