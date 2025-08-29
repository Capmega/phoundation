<?php

/**
 * Page my/reports
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// No get parameters allowed
GetValidator::new()->validate();


// Set page meta data
Response::setHeaderTitle(tr('My reports page'));
Response::setBreadcrumbs([
   Breadcrumb::new('/', tr('Home')),
   Breadcrumb::new('' , tr('Reports')),
]);


// Return the card
return Card::new()
           ->setTitle(tr('Reports available to me'))
           ->setContent(AnchorBlock::new(Url::new('/timesheets/my-timesheet-reports/review.html')->makeWww(), tr('Review & submit')) .
                        AnchorBlock::new(Url::new('/timesheets/my-timesheet-reports/summary.html')->makeWww(), tr('My summary report')) .
                        AnchorBlock::new(Url::new('/timesheets/my-timesheet-reports/detailed.html')->makeWww(), tr('My detailed report')));
