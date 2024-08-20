<?php

/**
 * Projects page
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
Response::setHeaderTitle(tr('Projects'));
Response::setHeaderSubTitle(tr('Demo'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'           => tr('Home'),
                                                           '/demos.html' => tr('Demos'),
                                                           ''            => tr('Projects'),
                                                       ]));

?>
<!-- Main content -->
<section class="content">

    <!-- Default box -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Projects</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped projects">
                <thead>
                <tr>
                    <th style="width: 1%">
                        #
                    </th>
                    <th style="width: 20%">
                        Project Name
                    </th>
                    <th style="width: 30%">
                        Team Members
                    </th>
                    <th>
                        Project Progress
                    </th>
                    <th style="width: 8%" class="text-center">
                        Status
                    </th>
                    <th style="width: 20%"></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="57" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 57%"></div>
                        </div>
                        <small>
                            57% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-success">Success</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="47" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 47%"></div>
                        </div>
                        <small>
                            47% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-warning">Delayed</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="77" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 77%"></div>
                        </div>
                        <small>
                            77% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-warning">behind schedule</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="60" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 60%"></div>
                        </div>
                        <small>
                            60% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-danger">Failed</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="12" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 0%"></div>
                        </div>
                        <small>
                            0% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-info">Planned</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="35" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 35%"></div>
                        </div>
                        <small>
                            35% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-success">Success</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="87" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 87%"></div>
                        </div>
                        <small>
                            87% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-success">Success</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="77" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 77%"></div>
                        </div>
                        <small>
                            77% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-success">Success</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        #
                    </td>
                    <td>
                        <a>
                            AdminLTE v3
                        </a>
                        <br />
                        <small>
                            Created 01.01.2019
                        </small>
                    </td>
                    <td>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                            <li class="list-inline-item">
                                <?= Session::getUserObject()
                                           ->getImageFileObject()
                                               ->getImgObject()
                                                   ->setSrc(Url::getImg('img/profiles/default.png'))
                                                   ->setClass('table-avatar')
                                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                                   ->render() ?>
                            </li>
                        </ul>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="77" aria-valuemin="0"
                                 aria-valuemax="100" style="width: 77%"></div>
                        </div>
                        <small>
                            77% Complete
                        </small>
                    </td>
                    <td class="project-state">
                        <span class="badge badge-success">Success</span>
                    </td>
                    <td class="project-actions text-right">
                        <a class="btn btn-primary btn-sm"
                           href="<?= Url::getWww('demos/project-detail.html'); ?>">
                            <i class="fas fa-folder">
                            </i>
                            View
                        </a>
                        <a class="btn btn-info btn-sm" href="<?= Url::getWww('demos/project-edit.html'); ?>">
                            <i class="fas fa-pencil-alt">
                            </i>
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#">
                            <i class="fas fa-trash">
                            </i>
                            Delete
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->

</section>
