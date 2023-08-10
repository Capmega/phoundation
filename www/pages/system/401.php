<?php

declare(strict_types=1);

use Phoundation\Templates\Template;
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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
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