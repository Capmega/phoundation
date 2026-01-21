<?php

/**
 * Page index
 *
 * This is the default page redirected to from sign-in. It is useful as a dashboard, show messages, etc
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Requests\Response;


// This page accepts no GET parameters
GetValidator::new()->validate();


// Set page meta-data
Response::setPageTitle(tr('Dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUserObject()->getDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Dashboard')),
]);
