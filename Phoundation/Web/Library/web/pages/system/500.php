<?php

/**
 * Page 500
 *
 * This is the page that will be shown when the system encounters an internal error from which it could not recover
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
        Response::setHttpCode(500);
        Json::reply(['error' => tr('Internal server error')]);
}


// Build the error page
echo Template::new('system/http-error')->setSource([
                                                       ':h2'     => '500',
                                                       ':h3'     => tr('Internal Server Error'),
                                                       ':img'    => Url::getImg('backgrounds/' . Core::getProjectSeoName() . '/404/large.jpg'),
                                                       ':p'      => tr('The server encountered an internal error and could not fulfill your request. Please contact the system administrator'),
                                                       ':type'   => 'warning',
                                                       ':search' => tr('Search'),
                                                       ':action' => Url::getWww('search/'),
                                                   ])->render();


// Set page meta data
Response::setHttpCode(500);
Response::setRenderMainWrapper(false);
Response::setPageTitle('500 - Internal Server Error');
Response::setHeaderTitle(tr('500 - Error'));
Response::setDescription(tr('The server encountered an internal error and could not fulfill your request'));
Response::setBreadCrumbs();
