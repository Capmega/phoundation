<?php

use Composer\DependencyResolver\Request;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\NoPasswordSpecifiedException;
use Phoundation\Accounts\Users\Exception\PasswordTooShortException;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Config;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


/**
 * Page sign-in
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Only show sign-in page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Response::redirect('prev', 302, reason_warning: tr('Sign-in page is only available to guest users'));
}


// Is email specified by URL?
try {
    $get = GetValidator::new()
        ->select('email')->isOptional()->isEmail()
        ->select('redirect')->isOptional()->isUrl()
        ->validate();

} catch (ValidationFailedException $e) {
    // If validation failed, this means that either the specified email or redirect variables were invalid. Redirect to
    // a clean sign-in page to ensure we have valid values
    Response::redirect('sign-in');
}


// Validate sign in data and sign in
if (Request::isPostRequestMethod()) {
    // Try to authenticate against UserRec first. If that fails, authenticate against User.
    foreach ([User::class] as $user_class) {
        try {
            $redirect = $get['redirect'];
            $post     = Session::validateSignIn();
            $user     = Session::signIn($post['email'], $post['password'], $user_class);

            Response::redirect(UrlBuilder::getRedirect($redirect, $user->getDefaultPage()));

        } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
            Request::getFlashMessages()->addWarningMessage(tr('Please specify at least ":count" characters for the password', [
                ':count' => Config::getInteger('security.passwords.size.minimum', 10)
            ]));

            break;

        } catch (ValidationFailedException $e) {
            Request::getFlashMessages()->addWarningMessage(tr('Please specify a valid email and password'));
            break;

        } catch (AuthenticationException $e) {
            Request::getFlashMessages()->addWarningMessage(tr('The specified email and/or password were incorrect'));
        }
    }

    if (empty($get['email'])) {
        $get['email'] = PostValidator::new()->getSourceKey('email');
    }
}


// This page will build its own body
Response::setBuildBody(false);

?>
<?= Request::getFlashMessages()->render() ?>
<body class="hold-transition login-page" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Core::getProjectSeoName() . '/signin.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
      <!-- /.login-logo -->
      <div class="card card-outline card-info">
        <div class="card-header text-center">
          <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Phoun</span>dation'); ?></a>
        </div>
        <div class="card-body">
          <p class="login-box-msg"><?= tr('Please sign in to start your session') ?></p>

          <form action="<?= UrlBuilder::getWww() ?>" method="post">
            <?php
                if (Session::supports('email')) {
                    ?>
                    <div class="input-group mb-3">
                        <input type="email" name="email" id="email" class="form-control" placeholder="<?= tr('Email address') ?>"<?= isset_get($get['email']) ? 'value="' . $get['email'] . '"' : '' ?>>
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

                if (Session::supports('lost-password')) {
                    echo '  <p class="mb-1">
                                <a href="' . UrlBuilder::getWww('/lost-password.html')->addQueries(isset_get($post['email']) ? 'email=' . $post['email'] : '', isset_get($post['redirect']) ? 'redirect=' . $post['redirect'] : '') . '">' . tr('I forgot my password') . '</a>
                            </p>';
                }

                if (Session::supports('register')) {
                    echo '  <p class="mb-0">
                                <a href="' . UrlBuilder::getWww('/sign-in.html') . '" class="text-center">' . tr('Register a new membership') . '</a>
                            </p>';
                }
        ?>
        <div class="login-footer text-center">
            <?= 'Copyright © ' . Config::getString('project.copyright', '2023') . ' <b><a href="' . Config::getString('project.owner.url', 'https://phoundation.org') . '" target="_blank">' . Config::getString('project.owner.name', 'Phoundation') . '</a></b><br>'; ?>
            All rights reserved        </div>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
</body>
<?php


// Set page meta data
Response::setPageTitle(tr('Please sign in'));
