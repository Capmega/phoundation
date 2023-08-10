<?php

declare(strict_types=1);

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


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
