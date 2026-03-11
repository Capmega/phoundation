<?php

/**
 * Lock screen page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// This page accepts no query variables whatsoever
GetValidator::new()->validate();


// Set page meta-data
Response::setHeaderTitle(tr('Lock screen'));
Response::setHeaderSubTitle(tr('Demo'));


// This page will build its own body
Response::setRenderMainWrapper(false);


?>
<body class="hold-transition lockscreen"
      style="background: url(<?= Url::new('backgrounds/fingerprint-screen.jpg')->makeImg() ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper card card-outline card-info">
    <div class="card-header text-center">
        <div class="lockscreen-logo">
            <?= Anchor::new(Project::getOwnerUrl())
                      ->setContent(Project::getOwnerLabel(), false)
                      ->setClass('h1'); ?>
        </div>
    </div>
    <div class="card-body">
        <!-- User name -->
        <div class="lockscreen-name">
            <div class="fingerprint-image">
                <?= Session::getUserObject()
                           ->getProfilePictureFileObject()
                               ->getImgObject()
                                   ->addClasses('rounded-circle')
                                   ->setSrc(Url::new('img/fingerprint-256x192.png')->makeImg())
                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUsersDisplayName())]))
                                   ->render() ?>
            </div>
        </div>

        <!-- /.lockscreen-item -->
        <div class="help-block text-center no-break">
            Please authenticate using the fingerprint scanner to continue
        </div>
        <div class="text-center">
            <?=
                Anchor::new(Url::new('sign-out'))
                      ->setContent(tr('Or sign in as a different user'));
            ?>
        </div>
        <div class="lockscreen-footer text-center">
            <?= Project::getCopyrightString() ?>
        </div>
    </div>
</div>
<!-- /.center -->
</body>
