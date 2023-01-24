<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '404',
    ':h3'     => tr('Page not found'),
    ':p'      => tr('We could not find the page you were looking for', [
            ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
Page::setHttpCode(404);
Page::setBuildBody(false);
Page::setPageTitle('404 - Page not found');
Page::setHeaderTitle(tr('404 - Error'));
Page::setDescription(tr('The specified page is not found'));
Page::setBreadCrumbs();