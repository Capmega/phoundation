<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

echo Template::page('admin/system/detail-error')->render([
    ':h2'     => '500',
    ':h3'     => tr('500 Internal Server Error'),
    ':p'      => tr('The server encountered an internal error and could not fulfill your request. Please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('500 - Internal Server Error');
WebPage::setHeaderTitle(tr('500 - Error'));
WebPage::setDescription(tr('The server encountered an internal error and could not fulfill your request'));
WebPage::setBreadCrumbs();






