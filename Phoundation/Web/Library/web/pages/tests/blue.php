<?php

/**
 * Page tests/blue.html
 *
 * This is a test page that will display a 100% blue background
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Requests\Response;


// This page does not permit any variables
GetValidator::new()->validate();


// Set the background to white
Response::setClass('content-wrapper bg-blue', 'content-wrapper');
Response::setPageTitle(tr('Test page'));
Response::setHeaderTitle(tr('Blue'));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/'                , tr('Home')),
    Breadcrumb::new('/tests/index.html', tr('Tests')),
    Breadcrumb::new(''                 , tr('Blue')),
]);
