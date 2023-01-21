<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '401',
    ':h3'     => tr('Unauthorized'),
    ':p'      => tr('You need to login to access the specified resource. Please contact the system administrator if you think this was in error', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
Page::setBuildBody(false);
Page::setPageTitle('401 - Unauthorized');
Page::setHeaderTitle(tr('401 - Error'));
Page::setDescription(tr('You need to login to access the specified resource'));
Page::setBreadCrumbs();