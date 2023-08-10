<?php

declare(strict_types=1);

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 503
 *
 * This is the page that will be shown when the system is down for maintenance
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '503',
    ':h3'     => tr('503 Service Unavailable'),
    ':p'      => tr('The server is under maintenance and will return momentarily. Please contact the system administrator for more information', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(503);
Page::setBuildBody(false);
Page::setPageTitle('503 - Service Unavailable');
Page::setHeaderTitle(tr('503 - Error'));
Page::setDescription(tr('The server is under maintenance and will return momentarily'));
Page::setBreadCrumbs();
