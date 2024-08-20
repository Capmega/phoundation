<?php

/**
 * Contact us page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Set page meta data
Response::setHeaderTitle(tr('Contacts'));
Response::setHeaderSubTitle(tr('Demo'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'           => tr('Home'),
                                                           '/demos.html' => tr('Demos'),
                                                           ''            => tr('Contacts'),
                                                       ]));

?>
<!-- Main content -->
<section class="content">

    <!-- Default box -->
    <div class="card card-solid">
        <div class="card-body pb-0">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            CEO
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Kate Culter</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            COO
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Corey Henrichsen</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            CTO
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Sven Olaf Oostenbrink</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            Digital Strategist
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b>Nicole Pearson</b></h2>
                                    <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist /
                                        Coffee Lover </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i
                                                        class="fas fa-lg fa-building"></i></span> Address: Demo Street
                                            123, Demo City 04312, NJ
                                        </li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span>
                                            Phone #: + 800 - 12 12 23 52
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <?= Session::getUserObject()
                                               ->getImageFileObject()
                                                   ->getImgObject()
                                                       ->setSrc(Url::getImg('img/profiles/default.png'))
                                                       ->setClass('img-circle img-fluid')
                                                       ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                       ->render() ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <a href="#" class="btn btn-sm bg-teal">
                                    <i class="fas fa-comments"></i>
                                </a>
                                <a href="<?= Url::getWww('demos/profile.html'); ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <nav aria-label="Contacts Page Navigation">
                <ul class="pagination justify-content-center m-0">
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">4</a></li>
                    <li class="page-item"><a class="page-link" href="#">5</a></li>
                    <li class="page-item"><a class="page-link" href="#">6</a></li>
                    <li class="page-item"><a class="page-link" href="#">7</a></li>
                    <li class="page-item"><a class="page-link" href="#">8</a></li>
                </ul>
            </nav>
        </div>
        <!-- /.card-footer -->
    </div>
    <!-- /.card -->

</section><!-- /.content -->
