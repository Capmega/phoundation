<?php

use Phoundation\Accounts\Users\Exception\NoPasswordSpecifiedException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\Exception\PasswordTooShortException;
use Phoundation\Core\Core;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Config;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page force-password-update
 *
 * This page forces users to update their password. Typically used when the user was just created with a default
 * password to force the user to use its own password
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Only allow being here when it was forced by redirect
if (!Session::getUser()->getRedirect() or (Session::getUser()->getRedirect() !== (string) UrlBuilder::getWww('/force-password-update.html'))) {
    Page::redirect('prev', 302, reason_warning: tr('Force password update is only available when it was accessed using forced user redirect'));
}


// Validate sign in data and sign in
if (Page::isPostRequestMethod()) {
    try {
        $post = PostValidator::new()
            ->select('password')->isPassword()
            ->select('passwordv')->isEqualTo('password')
            ->validate();

        // Update the password for this sessions user and remove the forced redirect to this page
        Session::getUser()
            ->changePassword($post['password'], $post['passwordv'])
            ->setRedirect()
            ->save();

        // Add flash message and redirect to original target
        Page::getFlashMessages()->addSuccessMessage(tr('Your password has been updated'));
        Page::redirect('prev');

    } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
        Page::getFlashMessages()->addWarningMessage(tr('Please specify at least ":count" characters for the password', [
            ':count' => Config::getInteger('security.passwords.size.minimum', 10)
        ]));

    } catch (ValidationFailedException $e) {
        Page::getFlashMessages()->addMessage($e);

    }catch (PasswordNotChangedException $e) {
        Page::getFlashMessages()->addWarningMessage(tr('You provided your current password. Please update your account to have a new and secure password'));
    }
}


// This page will build its own body
Page::setBuildBody(false);
?>
<?= Page::getFlashMessages()->render() ?>
    <body class="hold-transition login-page" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Core::getProjectSeoName() . '/password.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
              <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Phoun</span>dation'); ?></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= tr('Please update your account to have a new and secure password password before continuing...') ?></p>
                <p class="login-box-msg"><?= tr('Please ensure that your password has at least 10 characters, is secure, and is known only to you.') ?></p>

                <form action="<?= UrlBuilder::getWww() ?>" method="post">
                    <div class="input-group mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder="<?= tr('Password') ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="passwordv" id="passwordv" class="form-control" placeholder="<?= tr('Verify password') ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block"><?= tr('Update and continue') ?></button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <a href="<?= UrlBuilder::getWww('/sign-out.html') ?>" class="btn btn-outline-secondary btn-block"><?= tr('Sign out') ?></a>
                        </div>
                    </div>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    </body>
<?php


// Set page meta data
Page::setPageTitle(tr('Please update your password before continuing...'));
