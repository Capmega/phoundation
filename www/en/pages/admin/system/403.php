<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\WebPage;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '403',
    ':h3'     => tr('Forbidden'),
    ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
WebPage::setPageTitle('403 - Forbidden');
WebPage::setHeaderTitle(tr('403 - Error'));
WebPage::setDescription(tr('You do not have access to the specified resource'));
WebPage::setBreadCrumbs();






