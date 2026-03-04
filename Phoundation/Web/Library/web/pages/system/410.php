<?php

/**
 * Page 410 - Gone
 *
 * This is the page that will be shown when the requested resource is no longer available
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
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\JsonPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Get the exception
$e = Core::readRegister('e');


// JSON type pages should not return this HTML
switch (Request::getRequestType()) {
    case EnumRequestTypes::ajax:
        // no break

    case EnumRequestTypes::api:
        Response::setHttpCode(410);
        JsonPage::new()->reply(['error' => tr('Gone')]);
}


// Set page meta-data
Response::setHttpCode(410);
Response::setRenderMainWrapper(false);
Response::setPageTitle('410 - Gone');
Response::setHeaderTitle(tr('410 - Gone'));
Response::setDescription(Request::get('message') ?? tr('The specified request could not be completed because the requested resource ":url" is no longer available', [
    ':url' => Request::getReferer(true),
]));
Response::setBreadcrumbs();


// Render and return the system page
return Page::new('system/http-error')->addTextsObject([
    ':h2'     => '410',
    ':h3'     => tr('Gone'),
    ':img'    => Url::new('backgrounds/404/large.jpg')->makeImg(),
    ':p'      => Request::get('message') ?? tr('The specified request could not be completed because the requested resource ":url" is no longer available.', [
        ':url' => Request::getReferer(true),
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::new('search/')->makeWww()
]);
