<?php

declare(strict_types=1);


use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(404) The requested page was not found!'),
    ':p'      => tr('The page you requested to view does not exist on this server. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => Page::getReferer(true)
    ])
]);


// Set page meta data
Page::setHttpCode(404);
Page::setBuildBody(false);
Page::setPageTitle('404 - Page not found');
Page::setHeaderTitle(tr('404 - Error'));
Page::setDescription(tr('The specified page is not found'));
Page::setBreadCrumbs();