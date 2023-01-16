<?php

use Phoundation\Templates\Template;
use Phoundation\Web\WebPage;



// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(401) Unauthorized'),
    ':p'      => tr('You need to sign in to be able to access this information. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => WebPage::getReferer(true)
    ])
]);



// Set page meta data
WebPage::setPageTitle('401 - Unauthorized');
WebPage::setHeaderTitle(tr('401 - Error'));
WebPage::setDescription(tr('You need to sign in to be able to access this information'));
WebPage::setBreadCrumbs();
