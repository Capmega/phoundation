<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;

$page = Template::page('system/error');
echo $page->render([
    ':h2'     => '403',
    ':h3'     => tr('Forbidden'),
    ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::build('search/')->www()
]);

// Set page meta data
WebPage::setPageTitle('403 - Forbidden');
WebPage::setHeaderTitle(tr('403 - Forbidden'));
WebPage::setDescription(tr('You do not have access to the specified resource'));
WebPage::setBreadCrumbs();






