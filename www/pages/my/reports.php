<?php

use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page timesheets/redirect
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

// Set page meta data
Page::setHeaderTitle(tr('My reports page'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'          => tr('Home'),
    ''           => tr('Reports')
]));

echo Card::new()
    ->setTitle(tr('Reports available to me'))
    ->setContent('<a href="' . UrlBuilder::getWww('/timesheets/my-timesheet-reports/review.html') . '">' . tr('My timesheet review & submit') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/timesheets/my-timesheet-reports/summary.html') . '">' . tr('My timesheet summary report') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/timesheets/my-timesheet-reports/detailed.html') . '">' . tr('My timesheet detailed report') . '</a><hr>')
    ->render();
?>
