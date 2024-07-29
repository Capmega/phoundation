<?php

use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


/**
 * Page timesheets/redirect
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

// Set page meta data
Response::setHeaderTitle(tr('My reports page'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/' => tr('Home'),
                                                           ''  => tr('Reports'),
                                                       ]));

echo Card::new()
         ->setTitle(tr('Reports available to me'))
         ->setContent('<a href="' . Url::getWww('/timesheets/my-timesheet-reports/review.html') . '">' . tr('Review & submit') . '</a><hr>
                         <a href="' . Url::getWww('/timesheets/my-timesheet-reports/summary.html') . '">' . tr('My summary report') . '</a><br>
                         <a href="' . Url::getWww('/timesheets/my-timesheet-reports/detailed.html') . '">' . tr('My detailed report') . '</a><hr>')
         ->render();
?>
