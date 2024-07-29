<?php

/**
 * Page 404
 *
 * This is the page that will be shown when a user tries to access a page or resource that does not exist
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
        Response::setHttpCode(404);
        Json::reply(['error' => tr('Not Found')]);
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '404',
                                                       ':h3'     => tr('Page not found'),
                                                       ':img'    => Url::getImg('backgrounds/medinet-mobile/404/large.jpg'),
                                                       ':p'      => tr('We could not find the page you were looking for. Please go back where you came from!'),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':img'    => Url::getImg('img/backgrounds/' . Core::getProjectSeoName() . '/404/large.jpg'),
                                                       ':action' => Url::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(404);
Response::setRenderMainWrapper(false);
Response::setPageTitle('404 - Page not found');
Response::setHeaderTitle(tr('404 - Page not found'));
Response::setDescription(tr('The specified page is not found'));
Response::setBreadCrumbs();
