<?php

use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Url;


/**
 * Reports page
 *
 * This is the main reports index page showing all available reports pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
echo Card::new()
         ->setTitle(tr('Reports pages'))
         ->setContent('<a href="' . Url::getWww('/reports/timesheets.html') . '">' . tr('Timesheet reports') . '</a><br>
                         <a href="' . Url::getWww('/user-timesheets/user-timesheet-reports/detailed.html') . '">' . tr('Detailed report') . '</a><br>
                         <a href="' . Url::getWww('/user-timesheets/user-timesheet-reports/summary.html') . '">' . tr('Summary report') . '</a><br>
                         <a href="' . Url::getWww('/timesheets-approval/approval.html') . '">' . tr('Approval') . '</a><hr>')
         ->render();
