<?php

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


/**
 * Demo page
 *
 * This is the main demonstration page showcasing possible uses of various components
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


echo Card::new()
    ->setTitle(tr('Demo page links'))
    ->setContent('                  <a href="' . UrlBuilder::getWww('/demos/timeline.html') . '">' . tr('Package timeline') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/calendar.html') . '">' . tr('User calendar') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/contact-us.html') . '">' . tr('Contact us') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/contacts.html') . '">' . tr('Contacts') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/profile.html') . '">' . tr('Employee profile page') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/fingerprint-screen.html') . '">' . tr('Finger print detection') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/lock-screen.html') . '">' . tr('Lock screen') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/invoice.html') . '">' . tr('Invoice') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/kanban.html') . '">' . tr('Kanban project management board') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/projects.html') . '">' . tr('Projects') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/project-detail.html') . '">' . tr('Projects detail page') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/project-edit.html') . '">' . tr('Projects edit page') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/mailbox.html') . '">' . tr('Mail box') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/readmail.html') . '">' . tr('Read mail') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/compose.html') . '">' . tr('Compose mail') . '</a><br>
                  <a href="' . UrlBuilder::getWww('/demos/scanner/gallery.html') . '">' . tr('Scanner gallery') . '</a>')
    ->render();
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
                        Calendar
                    </h3>
                    <!-- tools card -->
                    <div class="card-tools">
                        <!-- button with a dropdown -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown" data-offset="-52">
                                <i class="fas fa-bars"></i>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a href="#" class="dropdown-item">Add new event</a>
                                <a href="#" class="dropdown-item">Clear events</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= UrlBuilder::getWww('calendar/calendar.html'); ?>" class="dropdown-item">View calendar</a>
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

// Required javascript
Response::loadJavascript('adminlte/plugins/moment/moment');
Response::loadJavascript('adminlte/plugins/daterangepicker/daterangepicker');
Response::loadJavascript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');

// Required CSS
Response::loadCss('adminlte/plugins/daterangepicker/daterangepicker');
Response::loadCss('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4');
Response::loadCss('https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css');

Script::new()
    ->setJavascriptWrapper(EnumJavascriptWrappers::window)
    ->setContent(' 
          $("#calendar").datetimepicker({
            format: "L",
            inline: true
          })');

// Set page meta data
Response::setPageTitle(tr('Demo dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUser()->getDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Demos')
]));
