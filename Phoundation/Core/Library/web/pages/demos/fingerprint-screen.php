<?php

use Phoundation\Core\Core;
use Phoundation\Core\Sessions\Session;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


/**
 * Lock screen page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

// Set page meta data
Response::setHeaderTitle(tr('Lock screen'));
Response::setHeaderSubTitle(tr('Demo'));

// This page will build its own body
Response::setRenderMainWrapper(false);

?>
<body class="hold-transition lockscreen"
      style="background: url(<?= Url::getImg('img/backgrounds/' . Core::getProjectSeoName() . '/fingerprint-screen.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
<!-- Automatic element centering -->
<div class="lockscreen-wrapper card card-outline card-info">
    <div class="card-header text-center">
        <div class="lockscreen-logo">
            <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>"
               class="h1"><?= Config::getString('project.owner.label', '<span>Medi</span>web'); ?></a>
        </div>
    </div>
    <div class="card-body">
        <!-- User name -->
        <div class="lockscreen-name">
            <div class="fingerprint-image">
                <?= Session::getUser()->getPicture()
                           ->getHtmlElement()
                           ->addClasses('rounded-circle')
                           ->setSrc(Url::getImg('img/fingerprint-256x192.png'))
                           ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUser()->getDisplayName())]))
                           ->render() ?>
            </div>
        </div>

        <!-- /.lockscreen-item -->
        <div class="help-block text-center no-break">
            Please authenticate using the fingerprint scanner to continue
        </div>
        <div class="text-center">
            <a href="<?= Url::getWww('sign-out'); ?>">Or sign in as a different user</a>
        </div>
        <div class="lockscreen-footer text-center">
            <?= 'Copyright © ' . Config::getString('project.copyright', '2024') . ' <b><a href="' . Config::getString('project.owner.url', 'https://phoundation.org') . '" target="_blank">' . Config::getString('project.owner.name', 'Phoundation') . '</a></b><br>'; ?>
            All rights reserved
        </div>
    </div>
</div>
<!-- /.center -->
</body>
