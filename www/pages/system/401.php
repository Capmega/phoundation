<?php

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 401
 *
 * This is the page that will be shown when a user tries to access a page or resource that requires an account, but
 * redirection to sign-in was not done
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
        Page::setHttpCode(401);
        Json::reply(['error' => tr('Authentication required')]);
}


// Add this exception as a flash message
Page::getFlashMessages()->addMessage($e);


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '401',
    ':h3'     => tr('Unauthorized'),
    ':p'      => tr('You need to login to access the specified resource. Please contact the system administrator if you think this was in error', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(401);
Page::setBuildBody(false);
Page::setPageTitle('401 - Unauthorized');
Page::setHeaderTitle(tr('401 - Error'));
Page::setDescription(tr('You need to login to access the specified resource'));
Page::setBreadCrumbs();