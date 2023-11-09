<?php

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Core\Config;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Only show sign-in page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Page::redirect('prev', 302);
}


// Validate sign in data and sign in
if (Page::isPostRequestMethod()) {
    try {
        $post = Session::validateSignIn();
        Session::signIn($post['email'], $post['password']);
        Page::redirect(UrlBuilder::getPrevious('/sign-in.html'));

    } catch (ValidationFailedException) {
        Page::getFlashMessages()->addWarningMessage(tr('Access denied'), tr('Please specify a valid email and password'));
    } catch (AuthenticationException) {
        Page::getFlashMessages()->addWarningMessage(tr('Access denied'), tr('The specified email or password was incorrect'));
    }
}


// This page will build its own body
Page::setBuildBody(false);

?>
<?= Page::getFlashMessages()->render() ?>
    <body class="hold-transition login-page" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Page::getProjectName() . '/lost-password.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
              <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Phoun</span>dation'); ?></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= tr('Please provide your email address and we will send you a link where you can re-establish your password') ?></p>

                <form action="<?= UrlBuilder::getWww() ?>" method="post">
                    <?php
                    if (Session::supports('email')) {
                        ?>
                        <div class="input-group mb-3">
                            <input type="email" name="email" id="email" class="form-control" placeholder="<?= tr('Email address') ?>">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <!-- /.col -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block"><?= tr('Request a new password') ?></button>
                            </div>
                            <!-- /.col -->
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a class="btn btn-outline-secondary btn-block" href="<?= UrlBuilder::getWww('/sign-in.html') ?>"><?= tr('Back to sign in') ?></a>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    </body>
<?php


// Set page meta data
Page::setPageTitle(tr('Request a new password'));
