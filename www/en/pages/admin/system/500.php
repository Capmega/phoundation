<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '500',
    ':h3'     => tr('500 Internal Server Error'),
    ':p'      => tr('The server encountered an internal error and could not fulfill your request. Please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);

// Set page meta data
Page::setPageTitle('500 - Internal Server Error');
Page::setHeaderTitle(tr('500 - Error'));
Page::setDescription(tr('The server encountered an internal error and could not fulfill your request'));
Page::setBreadCrumbs();






