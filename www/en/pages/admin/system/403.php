<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '403',
    ':h3'     => tr('Forbidden'),
    ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
Page::setBuildBody(false);
Page::setPageTitle('403 - Forbidden');
Page::setHeaderTitle(tr('403 - Error'));
Page::setDescription(tr('You do not have access to the specified resource'));
Page::setBreadCrumbs();