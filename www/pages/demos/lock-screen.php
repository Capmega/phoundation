<?php

use Phoundation\Core\Core;
use Phoundation\Core\Sessions\Session;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Lock screen page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

// Set page meta data
Page::setHeaderTitle(tr('Lock screen'));
Page::setHeaderSubTitle(tr('Demo'));

// This page will build its own body
Page::setBuildBody(false);

?>
<body class="hold-transition lockscreen" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Core::getProjectSeoName() . '/lock-screen.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <!-- Automatic element centering -->
    <div class="lockscreen-wrapper card card-outline card-info">
        <div class="card-header text-center">
            <div class="lockscreen-logo">
                <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Medi</span>web'); ?></a><br>
                Screen is locked
            </div>
        </div>
        <div class="card-body">
          <!-- User name -->
          <div class="lockscreen-name">John Doe</div>

          <!-- START LOCK SCREEN ITEM -->
          <div class="lockscreen-item">
            <!-- lockscreen image -->
            <div class="lockscreen-image">
                <?= Session::getUser()->getPicture()
                    ->getHtmlElement()
                    ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
                    ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUser()->getDisplayName())]))
                    ->render() ?>
            </div>
            <!-- /.lockscreen-image -->

            <!-- lockscreen credentials (contains the form) -->
            <form class="lockscreen-credentials">
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
            Enter your password to retrieve your session
          </div>
          <div class="text-center">
            <a href="<?= UrlBuilder::getWww('sign-out.html'); ?>">Or sign in as a different user</a>
          </div>
          <div class="lockscreen-footer text-center">
              <?= 'Copyright © ' . Config::getString('project.copyright', '2024') . ' <b><a href="' . Config::getString('project.owner.url', 'https://phoundation.org') . '" target="_blank">' . Config::getString('project.owner.name', 'Phoundation') . '</a></b><br>'; ?>
            All rights reserved
          </div>
        </div>
    </div>
    <!-- /.center -->
</body>
