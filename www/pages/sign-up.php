<?php

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Core\Config;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page sign-up
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
Page::execute('system/404');

// Only show sign-up page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Page::redirect('prev', 302);
}


// Validate sign in data and sign in
if (Page::isPostRequestMethod()) {
    try {
        $post = Session::validateSignIn();
        Session::signIn($post['email'], $post['password']);
        Page::redirect('prev');

    } catch (ValidationFailedException) {
        Page::getFlashMessages()->addWarningMessage(tr('Please specify a valid email and password'));
    } catch (AuthenticationException) {
        Page::getFlashMessages()->addWarningMessage(tr('The specified email and/or password were incorrect'));
    }
}


// This page will build its own body
Page::setBuildBody(false);

?>
<?= Page::getFlashMessages()->render() ?>
<body class="hold-transition register-page">
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Phoun</span>dation'); ?></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Register a new membership</p>

                <form action="../../index.html" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Full name">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Retype password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="agreeTerms" name="terms" value="agree">
                                <label for="agreeTerms">
                                    I agree to the <a href="#">terms</a>
                                </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

                <div class="social-auth-links text-center">
                    <a href="#" class="btn btn-block btn-primary">
                        <i class="fab fa-facebook mr-2"></i>
                        Sign up using Facebook
                    </a>
                    <a href="#" class="btn btn-block btn-danger">
                        <i class="fab fa-google-plus mr-2"></i>
                        Sign up using Google+
                    </a>
                </div>

                <a href="login.html" class="text-center">I already have a membership</a>
            </div>
            <!-- /.form-box -->
            <div class="login-footer text-center">
                <?= 'Copyright © ' . Config::getString('project.copyright', '2023') . ' <b><a href="' . Config::getString('project.owner.url', 'https://phoundation.org') . '" target="_blank">' . Config::getString('project.owner.name', 'Phoundation') . '</a></b><br>'; ?>
                All rights reserved
            </div>
        </div>

        </div><!-- /.card -->
    </div>
    <!-- /.register-box -->
</body>
<?php


// Set page meta data
Page::setPageTitle(tr('Please sign in'));
