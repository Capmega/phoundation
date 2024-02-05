<?php

declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Templates\Template;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 409 - Conflict
 *
 * This is the page that will be shown when the specified could not be completed due to a conflict with the current
 * state of the target resource
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
        Page::setHttpCode(409);
        Json::reply(['error' => tr('Conflict')]);
}


// Add this exception as a flash message
Page::getFlashMessages()->addMessage($e);


// Build the error page
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '409',
    ':h3'     => tr('Conflict'),
    ':p'      => tr('The specified could not be completed due to a conflict with the current state of the target resource.', [
            ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(409);
Page::setBuildBody(false);
Page::setPageTitle('409 - Conflict');
Page::setHeaderTitle(tr('409 - Conflict'));
Page::setDescription(tr('The specified could not be completed due to a conflict with the current state of the target resource'));
Page::setBreadCrumbs();
