<?php

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Requests\Response;

// Set page meta data
Response::setPageTitle(tr('Dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUser()->getDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Dashboard'),
                                                       ]));
