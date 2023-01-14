<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

$page = Template::page('system/error');
echo $page->render([
    ':h2'     => '401',
    ':h3'     => tr('Forbidden'),
    ':p'      => tr('You need to login to access the specified resource. Please contact the system administrator if you think this was in error. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('401 - Unauthorized');
WebPage::setHeaderTitle(tr('401 - Unauthorized'));
WebPage::setDescription(tr('You need to login to access the specified resource'));
WebPage::setBreadCrumbs();






