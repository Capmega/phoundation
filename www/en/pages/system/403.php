<?php

use Phoundation\Templates\Template;
use Phoundation\Web\Page;



// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'  => tr('(403) Forbidden!'),
    ':p'   => tr('You need to sign in to be able to access this information. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
    ':url' => Page::getReferer(true)
    ])
]);



// Set page meta data
Page::setPageTitle(tr('403 - Forbidden'));
Page::setHeaderTitle('');
Page::setDescription(tr('403 - Forbidden: You do not have access to the requested resource on this server'));
Page::setBreadCrumbs();
