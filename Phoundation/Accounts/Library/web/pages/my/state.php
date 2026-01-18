<?php

/**
 * Page my/state.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Requests\Response;


// No get parameters allowed
GetValidator::new()->validate();


// Set page meta-data
Response::setHeaderTitle(tr('My session sate'));
Response::setHeaderSubTitle(Session::getUserObject()->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'       , tr('Home')),
    Breadcrumb::new('/my.html', tr('My pages')),
    Breadcrumb::new(''        , tr('My state information')),
]);


show(Session::getStateObject()->getSource());
show(Session::getStateObject()->getPage());

