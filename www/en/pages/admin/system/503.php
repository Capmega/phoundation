<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

$page = Template::page('system/error');
echo $page->render([
    ':h2'     => '503',
    ':h3'     => tr('503 Service Unavailable'),
    ':p'      => tr('The server is under maintenance and will return momentarily. Please contact the system administrator for more information. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('503 - Service Unavailable');
WebPage::setHeaderTitle(tr('503 - Service Unavailable'));
WebPage::setDescription(tr('The server is under maintenance and will return momentarily'));
WebPage::setBreadCrumbs();






