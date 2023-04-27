<?php

declare(strict_types=1);


use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(401) Unauthorized'),
    ':p'      => tr('You need to sign in to be able to access this information. If you think this was in error, please contact the system administrator. Meanwhile, you may <a href=":url">return to dashboard</a> or try using the search form.', [
        ':url' => Page::getReferer(true)
    ]),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => UrlBuilder::getWww('search/')
]);


// Set page meta data
Page::setHttpCode(401);
Page::setBuildBody(false);
Page::setPageTitle('401 - Unauthorized');
Page::setHeaderTitle(tr('401 - Error'));
Page::setDescription(tr('You need to login to access the specified resource'));
Page::setBreadCrumbs();