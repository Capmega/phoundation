<?php

use Phoundation\Web\WebPage;
use Templates\AdminLte\Components\BreadCrumbs;
?>
<div class="container">
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
        <div class="text-center">
            <h1>(404) the requested page was not found!</h1>
        </div>
    </div>
</div>
<?php
// Set page meta data
WebPage::setPageTitle(tr('404 - Page not found'));
WebPage::setHeaderTitle('');
WebPage::setDescription(tr('404 - Page not found: The page you requested does not (or no longer) exist on this server'));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/' => tr('Home'),
    ''  => tr('404')
]));
