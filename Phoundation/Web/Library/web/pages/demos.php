<?php

/**
 * Demo page
 *
 * This is the main demonstration page showcasing possible uses of various components
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// This page accepts no query variables whatsoever
GetValidator::new()->validate();


// Required javascript
Response::loadJavaScript('adminlte/plugins/moment/moment');
Response::loadJavaScript('adminlte/plugins/daterangepicker/daterangepicker');
Response::loadJavaScript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');


// Required CSS
Response::loadCss('adminlte/plugins/daterangepicker/daterangepicker');
Response::loadCss('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4');
Response::loadCss('https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css');


// ???
Script::new()
    ->setJavascriptWrapper(EnumJavascriptWrappers::window)
    ->setContent(' 
          $("#calendar").datetimepicker({
            format: "L",
            inline: true
          })');


// Set page meta-data
Response::setPageTitle(tr('Demo dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUsersDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Demos')),
]);


echo Card::new()
         ->setTitle(tr('Demo page links'))
         ->setContent(AnchorBlock::new(Url::new('/demos/timeline.html')->makeWww(), tr('Audit timeline')) .
                      AnchorBlock::new(Url::new('/demos/calendar.html')->makeWww(), tr('User calendar')) .
                      AnchorBlock::new(Url::new('/demos/contact-us.html')->makeWww(), tr('Contact us')) .
                      AnchorBlock::new(Url::new('/demos/contacts.html')->makeWww(), tr('Contacts')) .
                      AnchorBlock::new(Url::new('/demos/profile.html')->makeWww(), tr('Employee profile page')) .
                      AnchorBlock::new(Url::new('/demos/fingerprint-screen.html')->makeWww(), tr('Finger print detection')) .
                      AnchorBlock::new(Url::new('/demos/lock-screen.html')->makeWww(), tr('Lock screen')) .
                      AnchorBlock::new(Url::new('/demos/invoice.html')->makeWww(), tr('Invoice')) .
                      AnchorBlock::new(Url::new('/demos/kanban.html')->makeWww(), tr('Kanban project management board')) .
                      AnchorBlock::new(Url::new('/demos/projects.html')->makeWww(), tr('Projects')) .
                      AnchorBlock::new(Url::new('/demos/project-detail.html')->makeWww(), tr('Projects detail page')) .
                      AnchorBlock::new(Url::new('/demos/project-edit.html')->makeWww(), tr('Projects edit page')) .
                      AnchorBlock::new(Url::new('/demos/mailbox.html')->makeWww(), tr('Mail box')) .
                      AnchorBlock::new(Url::new('/demos/read-mail.html')->makeWww(), tr('Read mail')) .
                      AnchorBlock::new(Url::new('/demos/compose.html')->makeWww(), tr('Compose mail')) .
                      AnchorBlock::new(Url::new('/demos/scanner/gallery.html')->makeWww(), tr('Scanner gallery')))
?>
    <div class="row">
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>15</h3>

                    <p>Packages in process</p>
                </div>
                <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>43<sup style="font-size: 20px">%</sup></h3>

                    <p>Available claims processed</p>
                </div>
                <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>1484</h3>

                    <p>Claims processed this week</p>
                </div>
                <div class="icon">
                    <i class="ion ion-person-add"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>65</h3>

                    <p>Rejections this week</p>
                </div>
                <div class="icon">
                    <i class="ion ion-pie-graph"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- right col (We are only adding the ID to make the widgets sortable)-->
        <section class="col-lg-5 connectedSortable">
            <!-- Calendar -->
            <div class="card">
                <div class="card-header border-0">

                    <h3 class="card-title">
                        <i class="far fa-calendar-alt"></i>
                        Calendar </h3>
                    <!-- tools card -->
                    <div class="card-tools">
                        <!-- button with a dropdown -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown"
                                    data-offset="-52">
                                <i class="fas fa-bars"></i>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a href="#" class="dropdown-item">Add new event</a>
                                <a href="#" class="dropdown-item">Clear events</a>
                                <div class="dropdown-divider"></div>
                                <?= Anchor::new(Url::new('calendar/calendar.html'))
                                          ->setContent(tr('View calendar'))
                                          ->setClass('dropdown-item'); ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <!-- /. tools -->
                </div>
                <!-- /.card-header -->
                <div class="card-body pt-0">
                    <!--The calendar -->
                    <div id="calendar" style="width: 100%"></div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </section>
        <!-- right col -->
    </div>
<?php
