<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\WebPage;



echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '401',
    ':h3'     => tr('Unauthorized'),
    ':p'      => tr('You need to login to access the specified resource. Please contact the system administrator if you think this was in error. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::www('search/')
]);



// Set page meta data
WebPage::setPageTitle('401 - Unauthorized');
WebPage::setHeaderTitle(tr('401 - Error'));
WebPage::setDescription(tr('You need to login to access the specified resource'));
WebPage::setBreadCrumbs();






