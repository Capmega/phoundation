<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

$page = Template::page('system/detail-error');
echo $page->render([
    ':h2'     => '404',
    ':h3'     => tr('Page not found'),
    ':p'      => tr('We could not find the page you were looking for. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
            ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('404 - Page not found');
WebPage::setHeaderTitle(tr('404 - Error'));
WebPage::setDescription(tr('The specified page is not found'));
WebPage::setBreadCrumbs();






