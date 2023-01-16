<?php

use Phoundation\Templates\Template;
use Phoundation\Web\WebPage;



// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(404) The requested page was not found!'),
    ':p'      => tr('The page you requested to view does not exist on this server. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ])
]);



// Set page meta data
WebPage::setPageTitle(tr('404 - Page not found'));
WebPage::setHeaderTitle('');
WebPage::setDescription(tr('404 - Page not found: The page you requested does not (or no longer) exist on this server'));
WebPage::setBreadCrumbs();
