<?php

/**
 * Page index
 *
 * This is the default page redirected to from sign-in. It's useful as a dashboard, show messages, etc
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Requests\Response;


// Set page meta data
Response::setPageTitle(tr('Dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUserObject()->getDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Dashboard'),
                                                       ]));
