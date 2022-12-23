<?php

use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;

?>
<div class="container">
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
        <div class="text-center">
            <h1>(403) Forbidden!</h1>
        </div>
    </div>
</div>
<?php
// Set page meta data
WebPage::setPageTitle(tr('403 - Forbidden'));
WebPage::setHeaderTitle('');
WebPage::setDescription(tr('403 - Forbidden: You do not have access to the requested resource on this server'));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/' => tr('Home'),
    ''  => tr('403')
]));
