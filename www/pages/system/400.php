<?php

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Routing\Route;


/**
 * Page 400
 *
 * This is the page that will be shown when a user sent incorrect information (typically caused by a non caught
 * validation exception)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get the exception
$e = Core::readRegister('e');


// JSON type pages should not return this HTML
switch (Core::getRequestType()) {
    case EnumRequestTypes::ajax:
        // no break
    case EnumRequestTypes::api:
        Page::setHttpCode(400);
        Json::reply($e->getData());
}


// Add this exception as a flash message
Page::getFlashMessages()->addMessage($e);


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '400',
    ':h3'     => tr('400 Bad Request'),
    ':p'      => tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact the system administrator', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(400);
Page::setBuildBody(false);
Page::setPageTitle('400 - Bad Request');
Page::setHeaderTitle(tr('400 - Error'));
Page::setDescription(tr('You sent incorrect or invalid information and your request was denied'));
Page::setBreadCrumbs();
