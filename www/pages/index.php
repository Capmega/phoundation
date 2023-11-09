<?php

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Page;


// Set page meta data
Page::setPageTitle(tr('Dashboard'));
Page::setHeaderTitle(tr('Dashboard'));
Page::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUser()->getDisplayName()]));
Page::setDescription(tr(''));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Dashboard')
]));
