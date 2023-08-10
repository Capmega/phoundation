<?php

declare(strict_types=1);

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page 403
 *
 * This is the page that will be shown when a users access to a certain resource was prohibited
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '403',
    ':h3'     => tr('Forbidden'),
    ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(403);
Page::setBuildBody(false);
Page::setPageTitle('403 - Forbidden');
Page::setHeaderTitle(tr('403 - Error'));
Page::setDescription(tr('You do not have access to the specified resource'));
Page::setBreadCrumbs();