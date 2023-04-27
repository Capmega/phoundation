<?php

declare(strict_types=1);


use Phoundation\Templates\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(500) Internal Service Error'),
    ':p'      => tr('The server encountered an unexpected condition that prevented it from fulfilling the request')
]);


// Set page meta data
Page::setHttpCode(500);
Page::setBuildBody(false);
Page::setPageTitle('500 - Internal Server Error');
Page::setHeaderTitle(tr('500 - Error'));
Page::setDescription(tr('The server encountered an internal error and could not fulfill your request'));
Page::setBreadCrumbs();
