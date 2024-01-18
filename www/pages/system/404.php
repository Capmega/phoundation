<?php

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 404
 *
 * This is the page that will be shown when a user tries to access a page or resource that does not exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get the exception
$e = Core::readRegister('e');


// JSON type pages should not return this HTML
switch (Core::getRequestType()) {
    case EnumRequestTypes::ajax:
        // no break
    case EnumRequestTypes::api:
        Page::setHttpCode(404);
        Json::reply(['error' => tr('Not Found')]);
}


// Add this exception as a flash message
Page::getFlashMessages()->addMessage($e);


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '404',
    ':h3'     => tr('Page not found'),
    ':p'      => tr('We could not find the page you were looking for. Please go back where you came from!', [
            ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(404);
Page::setBuildBody(false);
Page::setPageTitle('404 - Page not found');
Page::setHeaderTitle(tr('404 - Page not found'));
Page::setDescription(tr('The specified page is not found'));
Page::setBreadCrumbs();
