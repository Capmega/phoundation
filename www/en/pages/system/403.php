<?php

use Phoundation\Templates\Template;
use Phoundation\Web\WebPage;



// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(403) Forbidden!'),
    ':p'      => tr('You need to sign in to be able to access this information. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ])
]);



// Set page meta data
WebPage::setPageTitle(tr('403 - Forbidden'));
WebPage::setHeaderTitle('');
WebPage::setDescription(tr('403 - Forbidden: You do not have access to the requested resource on this server'));
WebPage::setBreadCrumbs();
