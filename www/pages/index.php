<?php

use Phoundation\Core\Session;
use Phoundation\Web\Page;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;



// Set page meta data
Page::setPageTitle(tr('Dashboard'));
Page::setHeaderTitle(tr('Dashboard'));
Page::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUser()->getDisplayName()]));
Page::setDescription(tr(''));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Dashboard')
]));
