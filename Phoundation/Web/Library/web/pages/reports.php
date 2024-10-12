<?php

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


declare(strict_types=1);

use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Set page meta data
Response::setPageTitle(tr('Reports portal'));
Response::setHeaderTitle(tr('Reports portal'));
Response::setDescription(tr(''));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Reports portal'),
]));


// Build link cards
$timesheet_card = Card::new()
                      ->setTitle(tr('Timesheet reports'))
                      ->setContent('<a href="' . Url::getWww('/reports/timesheets.html') . '">' . tr('All timesheet reports') . '</a><hr>
                                    <a href="' . Url::getWww('/user-timesheets/user-timesheet-reports/detailed.html') . '">' . tr('Detailed report') . '</a><br>
                                    <a href="' . Url::getWww('/user-timesheets/user-timesheet-reports/summary.html') . '">' . tr('Summary report') . '</a>
                                    <hr>
                                    <a href="' . Url::getWww('/timesheets-approval/approval.html') . '">' . tr('Manage approvals') . '</a>');


$billing_card = Card::new()
                    ->setTitle(tr('Billing reports'))
                    ->setContent('<a href="' . Url::getWww('/reports/billing-reports/unpaid-claims.html') . '">' . tr('Unpaid claims') . '</a><br>
                                  <a href="' . Url::getWww('/reports/billing-reports/submissions.html') . '">' . tr('Submissions') . '</a><br>
                                  <a href="' . Url::getWww('/reports/billing-reports/submissions-all.html') . '">' . tr('All submissions') . '</a>');


$pharmanet_card = Card::new()
                      ->setTitle(tr('Pharmanet reports'))
                      ->setContent('<a href="' . Url::getWww('/reports/pharmanet.html') . '">' . tr('All PharmaNet reports') . '</a><br>
                                    <hr>
                                    <a href="' . Url::getWww('/pharmanet/pharmanet-reports/unique-users.html') . '">' . tr('PharmaNet Unique users') . '</a><br>
                                    <a href="' . Url::getWww('/pharmanet/pharmanet-reports/all-users.html') . '">' . tr('PharmaNet All users') . '</a>
                                    <hr>
                                    <a href="' . Url::getWww('reports/pharmanet/support/pin-codes/pharmanet.html') . '">' . tr('PharmaNet PIN codes report') . '</a><br>
                                    <a href="' . Url::getWww('reports/pharmanet/support/pin-codes/medinetmail.html') . '">' . tr('Medinet mail PIN codes report') . '</a>');


$security_card = Card::new()
                     ->setTitle(tr('Security reports'))
                     ->setContent('<a href="' . Url::getWww('/security/incidents.html') . '">' . tr('Incidents management') . '</a><br>
                                   <a href="' . Url::getWww('/security/authentications.html') . '">' . tr('Authentications management') . '</a>');


$sql_card = Card::new()
                ->setTitle(tr('SQL reports'))
                ->setContent('<a href="' . Url::getWww('/reports/sql.html') . '">' . tr('SQL report') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('<p>This reports portal contains a variety of links to the multitude of reports that are offered.</p><p>Just click on any of the reports to go there and return here by clicking on the "report" bread crumb above</p>');


// Render and return the grid
return Grid::new()
           ->addGridColumn($timesheet_card . $billing_card, EnumDisplaySize::three)
           ->addGridColumn($pharmanet_card                , EnumDisplaySize::three)
           ->addGridColumn($security_card . $sql_card     , EnumDisplaySize::three)
           ->addGridColumn($documentation_card            , EnumDisplaySize::three);
