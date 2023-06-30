<?php

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
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
        Page::redirect('prev');

    } catch (ValidationFailedException) {
        Page::getFlashMessages()->addFlashMessage(tr('Access denied'), tr('Please specify a valid email and password'), DisplayMode::warning);
    } catch (AuthenticationException) {
        Page::getFlashMessages()->addFlashMessage(tr('Access denied'), tr('The specified email or password was incorrect'), DisplayMode::warning);
    }
}


// This page will build its own body
Page::setBuildBody(false);
?>
<?= Page::getFlashMessages()->render() ?>
<body class="hold-transition login-page" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Page::getProjectName() . '/signin.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
      <!-- /.login-logo -->
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <a href="https://medinet.ca" class="h1"><b>Medi</b> web</a>
        </div>
        <div class="card-body">
          <p class="login-box-msg"><?= tr('Please sign in to start your session') ?></p>

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
                    <div class="input-group mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder="<?= tr('Password') ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember">
                                <label for="remember">
                                    <?= tr('Remember Me') ?>
                                </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block"><?= tr('Sign In') ?></button>
                        </div>
                        <!-- /.col -->
                    </div>
                    <?php
                }
            ?>
          </form>
          <?php
                $html = '';

                if (Session::supports('facebook')) {
                    $html .= '  <a href="#" class="btn btn-block btn-primary">
                                    <i class="fab fa-facebook mr-2"></i>' . tr('Sign in using Facebook') . ' 
                                </a>';
                }

                if (Session::supports('google')) {
                    $html .= '  <a href="#" class="btn btn-block btn-danger">
                                    <i class="fab fa-google-plus mr-2"></i>' . tr('Sign in using Google') . '
                                </a>';
                }

                if ($html) {
                    echo  ' <div class="social-auth-links text-center mt-2 mb-3">
                                ' . $html . '
                            </div>';
                }

                if (Session::supports('register')) {
                    echo '  <p class="mb-1">
                                <a href="' . UrlBuilder::getWww('/lost-password.html') . '">' . tr('I forgot my password') . '</a>
                            </p>';
                }

                if (Session::supports('register')) {
                    echo '  <p class="mb-0">
                                <a href="' . UrlBuilder::getWww('/sign-in.html') . '" class="text-center">' . tr('Register a new membership') . '</a>
                            </p>';
                }
        ?>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
</body>
<?php


// Set page meta data
Page::setPageTitle(tr('Setup Phoundation'));