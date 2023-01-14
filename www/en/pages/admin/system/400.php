<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

$page = Template::page('system/detail-error');
echo $page->render([
    ':h2'     => '400',
    ':h3'     => tr('400 Bad Request'),
    ':p'      => tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('400 - Bad Request');
WebPage::setHeaderTitle(tr('400 - Error'));
WebPage::setDescription(tr('You sent incorrect or invalid information and your request was denied'));
WebPage::setBreadCrumbs();






