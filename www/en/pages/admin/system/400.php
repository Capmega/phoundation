<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '400',
    ':h3'     => tr('400 Bad Request'),
    ':p'      => tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact the system administrator', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
Page::setBuildBody(false);
Page::setPageTitle('400 - Bad Request');
Page::setHeaderTitle(tr('400 - Error'));
Page::setDescription(tr('You sent incorrect or invalid information and your request was denied'));
Page::setBreadCrumbs();
