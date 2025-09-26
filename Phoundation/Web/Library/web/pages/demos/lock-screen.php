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
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// This page accepts no query variables whatsoever
GetValidator::new()->validate();


// Set page meta data
Response::setHeaderTitle(tr('Lock screen'));
Response::setHeaderSubTitle(tr('Demo'));

// This page will build its own body
Response::setRenderMainWrapper(false);

?>
<body class="hold-transition lockscreen"
      style="background: url(<?= Url::new('backgrounds/lock-screen.jpg')->makeImg() ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper card card-outline card-info">
    <div class="card-header text-center">
        <div class="lockscreen-logo">
            <?= AnchorBlock::new(Project::getOwnerUrl())
                           ->setContent(Project::getOwnerLabel(), false)
                           ->setClass('h1') .
                tr('Screen is locked');
            ?>
        </div>
    </div>
    <div class="card-body">
        <!-- User name -->
        <div class="lockscreen-name">John Doe</div>

        <!-- START LOCK SCREEN ITEM -->
        <div class="lockscreen-item">
            <!-- lockscreen image -->
            <div class="lockscreen-image">
                <?=
                    Session::getUserObject()
                           ->getProfilePictureFileObject()
                               ->getImgObject()
                                   ->setSrc(Url::new('img/profiles/default.png')->makeImg())
                                   ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUserObject()->getDisplayName())]))
                                   ->render()
                ?>
            </div>
            <!-- /.lockscreen-image -->

            <!-- lockscreen credentials (contains the form) -->
            <form class="lockscreen-credentials" method="post">
                <?php Csrf::getHiddenElement() ?>
                <div class="input-group">
                    <input type="password" class="form-control" placeholder="password">

                    <div class="input-group-append">
                        <button type="button" class="btn">
                            <i class="fas fa-arrow-right text-muted"></i>
                        </button>
                    </div>
                </div>
            </form>
            <!-- /.lockscreen credentials -->

        </div>
        <!-- /.lockscreen-item -->
        <div class="help-block text-center">
            <?= tr('Enter your password to retrieve your session'); ?>
        </div>
        <div class="text-center">
            <?=
                AnchorBlock::new(Url::new('sign-out'))
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
