<?php

declare(strict_types=1);


use Phoundation\Templates\Template;
use Phoundation\Web\Page;


// Display the template with the following information
echo Template::page('system/detail-error')->render([
    ':h1'     => tr('(503) Service unavailable'),
    ':p'      => tr('the server is currently unable to handle the request due to a temporary overload or scheduled maintenance')
]);


// Set page meta data
Page::setPageTitle('503 - Service Unavailable');
Page::setHeaderTitle(tr('503 - Error'));
Page::setDescription(tr('the server is currently unable to handle the request due to a temporary overload or scheduled maintenance'));
Page::setBreadCrumbs();
