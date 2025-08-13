<?php

/**
 * Read mail page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Set page meta data
Response::setHeaderTitle(tr('Read mail'));
Response::setHeaderSubTitle(tr('Demo'));
Response::setBreadcrumbs([
   Anchor::new('/'                  , tr('Home')),
   Anchor::new('/demos.html'        , tr('Demos')),
   Anchor::new('/demos/mailbox.html', tr('Mailbox')),
   Anchor::new(''                   , tr('Read mail')),
]);

?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <?=
                    Anchor::new(Url::new('mailbox.html'))
                          ->setContent(tr('Back to Inbox'))
                          ->setClass('btn btn-primary btn-block mb-3')
                ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Folders</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item active">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="fas fa-inbox"></i> ' . tr('Inbox') . '<span class="badge bg-primary float-right">12</span>')
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-envelope"></i> ' . tr('Sent'))
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-file-alt"></i> ' . tr('Drafts'))
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-filter"></i> ' . tr('Junk') . '<span class="badge bg-warning float-right">65</span>')
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-trash-alt"></i> ' . tr('Trash') . '<span class="badge bg-warning float-right">65</span>')
                                          ->setClass('nav-link')
                                ?>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Labels</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class='nav-item'>
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-circle text-danger"></i> ' . tr('Important'))
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-circle text-warning"></i> ' . tr('Promotions'))
                                          ->setClass('nav-link')
                                ?>
                            </li>
                            <li class="nav-item">
                                <?=
                                    Anchor::new(Url::new('#'))
                                          ->setContent('<i class="far fa-circle text-primary"></i> ' . tr('Social'))
                                          ->setClass('nav-link')
                                ?>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Read Mail</h3>

                        <div class="card-tools">
                            <?=
                                Anchor::new(Url::new('#'))
                                      ->setContent('<i class="fas fa-chevron-left"></i>')
                                      ->setClass('btn btn-tool')
                                      ->setTitle(tr('Previous'))
                            ?>
                            <?=
                                Anchor::new(Url::new('#'))
                                      ->setContent('<i class="fas fa-chevron-right"></i>')
                                      ->setClass('btn btn-tool')
                                      ->setTitle(tr('Next'))
                            ?>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <div class="mailbox-read-info">
                            <h5>Tracker system rocks!</h5>
                            <h6>From: sven@medinet.ca
                                <span class="mailbox-read-time float-right">15 Feb. 2015 11:03 PM</span></h6>
                        </div>
                        <!-- /.mailbox-read-info -->
                        <div class="mailbox-controls with-border text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm" data-container="body"
                                        title="Delete">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" data-container="body"
                                        title="Reply">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" data-container="body"
                                        title="Forward">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                            <!-- /.btn-group -->
                            <button type="button" class="btn btn-default btn-sm" title="Print">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                        <!-- /.mailbox-controls -->
                        <div class="mailbox-read-message">
                            <p>Hello Kate,</p>

                            <p>Keffiyeh blog actually fashion axe vegan, irony biodiesel. Cold-pressed hoodie chillwave
                                put a bird
                                on it aesthetic, bitters brunch meggings vegan iPhone. Dreamcatcher vegan scenester
                                mlkshk. Ethical
                                master cleanse Bushwick, occupy Thundercats banjo cliche ennui farm-to-table mlkshk
                                fanny pack
                                gluten-free. Marfa butcher vegan quinoa, bicycle rights disrupt tofu scenester chillwave
                                3 wolf moon
                                asymmetrical taxidermy pour-over. Quinoa tote bag fashion axe, Godard disrupt migas
                                church-key tofu
                                blog locavore. Thundercats cronut polaroid Neutra tousled, meh food truck selfies
                                narwhal American
                                Apparel.</p>

                            <p>Raw denim McSweeney's bicycle rights, iPhone trust fund quinoa Neutra VHS kale chips
                                vegan PBR&amp;B
                                literally Thundercats +1. Forage tilde four dollar toast, banjo health goth paleo
                                butcher. Four dollar
                                toast Brooklyn pour-over American Apparel sustainable, lumbersexual listicle gluten-free
                                health goth
                                umami hoodie. Synth Echo Park bicycle rights DIY farm-to-table, retro kogi sriracha
                                dreamcatcher PBR&amp;B
                                flannel hashtag irony Wes Anderson. Lumbersexual Williamsburg Helvetica next level.
                                Cold-pressed
                                slow-carb pop-up normcore Thundercats Portland, cardigan literally meditation
                                lumbersexual crucifix.
                                Wayfarers raw denim paleo Bushwick, keytar Helvetica scenester keffiyeh 8-bit irony
                                mumblecore
                                whatever viral Truffaut.</p>

                            <p>Post-ironic shabby chic VHS, Marfa keytar flannel lomo try-hard keffiyeh cray. Actually
                                fap fanny
                                pack yr artisan trust fund. High Life dreamcatcher church-key gentrify. Tumblr stumptown
                                four dollar
                                toast vinyl, cold-pressed try-hard blog authentic keffiyeh Helvetica lo-fi tilde
                                Intelligentsia. Lomo
                                locavore salvia bespoke, twee fixie paleo cliche brunch Schlitz blog McSweeney's
                                messenger bag swag
                                slow-carb. Odd Future photo booth pork belly, you probably haven't heard of them
                                actually tofu ennui
                                keffiyeh lo-fi Truffaut health goth. Narwhal sustainable retro disrupt.</p>

                            <p>Skateboard artisan letterpress before they sold out High Life messenger bag. Bitters
                                chambray
                                leggings listicle, drinking vinegar chillwave synth. Fanny pack hoodie American Apparel
                                twee. American
                                Apparel PBR listicle, salvia aesthetic occupy sustainable Neutra kogi. Organic synth
                                Tumblr viral
                                plaid, shabby chic single-origin coffee Etsy 3 wolf moon slow-carb Schlitz roof party
                                tousled squid
                                vinyl. Readymade next level literally trust fund. Distillery master cleanse migas, Vice
                                sriracha
                                flannel chambray chia cronut.</p>

                            <p>Have a great day!<br>Sven Olaf Oostenbrink</p>
                        </div>
                        <!-- /.mailbox-read-message -->
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer bg-white">
                        <ul class="mailbox-attachments d-flex align-items-stretch clearfix">
                            <li>
                                <span class="mailbox-attachment-icon"><i class="far fa-file-pdf"></i></span>

                                <div class="mailbox-attachment-info">
                                    <?=
                                        Anchor::new(Url::new('#'))
                                              ->setContent('<i class="fas fa-paperclip"></i> Sep2014-report.pdf')
                                              ->setClass('mailbox-attachment-name')
                                    ?>
                                    <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>1,245 KB</span>
                                        <?=
                                            Anchor::new(Url::new('#'))
                                                  ->setContent('<i class="fas fa-cloud-download-alt"></i>')
                                                  ->setClass('btn btn-default btn-sm float-right')
                                        ?>
                                    </span>
                                </div>
                            </li>
                            <li>
                                <span class="mailbox-attachment-icon"><i class="far fa-file-word"></i></span>

                                <div class="mailbox-attachment-info">
                                    <?=
                                        Anchor::new(Url::new('#'))
                                              ->setContent('<i class="fas fa-paperclip"></i> App Description.docx')
                                              ->setClass('mailbox-attachment-name')
                                    ?>
                                    <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>1,245 KB</span>
                                        <?=
                                            Anchor::new(Url::new('#'))
                                                  ->setContent('<i class="fas fa-cloud-download-alt"></i>')
                                                  ->setClass('btn btn-default btn-sm float-right')
                                        ?>
                                    </span>
                                    </div>
                            </li>
                            <li>
                                <span class="mailbox-attachment-icon has-img"><img
                                            src="<?= Url::new('/img/mail/test/image1.png')->makeCdn(); ?>"
                                            alt="Attachment"></span>

                                <div class="mailbox-attachment-info">
                                    <?=
                                        Anchor::new(Url::new('#'))
                                              ->setContent('<i class="fas fa-camera"></i> image1.png')
                                              ->setClass('mailbox-attachment-name')
                                    ?>
                                    <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>2.67 MB</span>
                                        <?=
                                            Anchor::new(Url::new('#'))
                                                  ->setContent('<i class="fas fa-cloud-download-alt"></i>')
                                                  ->setClass('btn btn-default btn-sm float-right')
                                        ?>
                                    </span>
                                </div>
                            </li>
                            <li>
                                <span class="mailbox-attachment-icon has-img"><img
                                            src="<?= Url::new('/img/mail/test/image2.png')->makeCdn(); ?>"
                                            alt="Attachment"></span>

                                <div class="mailbox-attachment-info">
                                    <?=
                                        Anchor::new(Url::new('#'))
                                              ->setContent('<i class="fas fa-camera"></i> image2.png')
                                              ->setClass('mailbox-attachment-name')
                                    ?>
                                    <span class="mailbox-attachment-size clearfix mt-1">
                                        <span>1.9 MB</span>
                                        <?=
                                            Anchor::new(Url::new('#'))
                                                  ->setContent('<i class="fas fa-cloud-download-alt"></i>')
                                                  ->setClass('btn btn-default btn-sm float-right')
                                        ?>
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- /.card-footer -->
                    <div class="card-footer">
                        <div class="float-right">
                            <?=
                                Anchor::new(Url::new('/demos/compose.html'))
                                      ->setContent('<i class="fas fa-reply"></i> ' . tr('Reply'))
                                      ->setClass('btn btn-default')
                                      ->setType('button');
                            ?>
                            <?=
                                Anchor::new(Url::new('/demos/compose.html'))
                                      ->setContent('<i class="fas fa-share"></i> ' . tr('Forward'))
                                      ->setClass('btn btn-default')
                                      ->setType('button');
                            ?>
                        </div>
                        <button type="button" class="btn btn-default"><i class="far fa-trash-alt"></i> Delete</button>
                        <button type="button" class="btn btn-default"><i class="fas fa-print"></i> Print</button>
                    </div>
                    <!-- /.card-footer -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</section>
