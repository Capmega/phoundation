<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '503',
    ':h3'     => tr('503 Service Unavailable'),
    ':p'      => tr('The server is under maintenance and will return momentarily. Please contact the system administrator for more information. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
Page::setPageTitle('503 - Service Unavailable');
Page::setHeaderTitle(tr('503 - Error'));
Page::setDescription(tr('The server is under maintenance and will return momentarily'));
Page::setBreadCrumbs();
