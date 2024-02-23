<?php

use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Page;


/**
 * Project page
 *
 * This is the main demonstration page showcasing possible uses of various components
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Set page meta data
Page::setHeaderTitle(tr('Package timeline'));
Page::setHeaderSubTitle(tr('demo'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
'/'           => tr('Home'),
'/demos.html' => tr('Demos'),
''            => tr('Package timeline')
]));
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Timelime example  -->
        <div class="row">
            <div class="col-md-12">
                <!-- The time line -->
                <div class="timeline">
                    <!-- timeline time label -->
                    <div class="time-label">
                        <span class="bg-red">10 Feb. 2014</span>
                    </div>
                    <!-- /.timeline-label -->
                    <!-- timeline item -->
                    <div>
                        <i class="fas fa-envelope bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 12:05</span>
                            <h3 class="timeline-header"><a href="#">Jen Smart</a> Did package intake</h3>

                            <div class="timeline-body">
                                Etsy doostang zoodles disqus groupon greplin oooj voxy zoodles,
                                weebly ning heekya handango imeem plugg dopplr jibjab, movity
                                jajah plickers sifteo edmodo ifttt zimbra. Babblely odeo kaboodle
                                quora plaxo ideeli hulu weebly balihoo...
                            </div>
                            <div class="timeline-footer">
                                <a class="btn btn-primary btn-sm">Read more</a>
                                <a class="btn btn-danger btn-sm">Delete</a>
                            </div>
                        </div>
                    </div>
                    <!-- END timeline item -->
                    <!-- timeline item -->
                    <div>
                        <i class="fas fa-user bg-green"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 5 mins ago</span>
                            <h3 class="timeline-header no-border"><a href="#">Meredith Bramham</a> Assigned package to Lauren Alderson</h3>
                        </div>
                    </div>
                    <!-- END timeline item -->
                    <!-- timeline item -->
                    <div>
                        <i class="fas fa-comments bg-yellow"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 27 mins ago</span>
                            <h3 class="timeline-header"><a href="#">Lauren Alderson</a> started processing part 1</h3>
                            <div class="timeline-body">
                                Package is almost ready!
                            </div>
                            <div class="timeline-footer">
                                <a class="btn btn-warning btn-sm">View comment</a>
                            </div>
                        </div>
                    </div>
                    <!-- END timeline item -->
                    <!-- timeline time label -->
                    <div class="time-label">
                        <span class="bg-green">3 Jan. 2014</span>
                    </div>
                    <!-- /.timeline-label -->
                    <!-- timeline item -->
                    <div>
                        <i class="fa fa-camera bg-purple"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 2 days ago</span>
                            <h3 class="timeline-header"><a href="#">Mina Lee</a> started processing part 2</h3>
                            <div class="timeline-body">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                            </div>
                        </div>
                    </div>
                    <!-- END timeline item -->
                    <!-- timeline item -->
                    <div>
                        <i class="fa fa-camera bg-purple"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 1 days ago</span>
                            <h3 class="timeline-header"><a href="#">Mina Lee</a> Finished processing part 2</h3>
                            <div class="timeline-body">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                                <img src="https://placehold.it/150x100" alt="...">
                            </div>
                        </div>
                    </div>
                    <!-- timeline item -->
                    <div>
                        <i class="fas fa-video bg-maroon"></i>

                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> 5 days ago</span>

                            <h3 class="timeline-header"><a href="#">Mr. Doe</a> shared a video</h3>

                            <div class="timeline-body">
                                <div class="embed-responsive embed-responsive-16by9">
                                    <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/tMWkeBIohBs" allowfullscreen></iframe>
                                </div>
                            </div>
                            <div class="timeline-footer">
                                <a href="#" class="btn btn-sm bg-maroon">See comments</a>
                            </div>
                        </div>
                    </div>
                    <!-- END timeline item -->
                    <div>
                        <i class="fas fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
            <!-- /.col -->
        </div>
    </div>
    <!-- /.timeline -->

</section>
<!-- /.content -->
