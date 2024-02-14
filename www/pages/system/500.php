<?php

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 500
 *
 * This is the page that will be shown when the system encounters an internal error from which it could not recover
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
        Page::setHttpCode(500);
        Json::reply(['error' => tr('Internal server error')]);
}


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '500',
    ':h3'     => tr('500 Internal Server Error'),
    ':p'      => tr('The server encountered an internal error and could not fulfill your request. Please contact the system administrator'),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(500);
Page::setBuildBody(false);
Page::setPageTitle('500 - Internal Server Error');
Page::setHeaderTitle(tr('500 - Error'));
Page::setDescription(tr('The server encountered an internal error and could not fulfill your request'));
Page::setBreadCrumbs();
