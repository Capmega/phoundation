<?php

use Phoundation\Accounts\Users\Exception\NoPasswordSpecifiedException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\Exception\PasswordTooShortException;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Pages\ForcePasswordUpdatePage;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


/**
 * Page force-password-update
 *
 * This page forces users to update their password. Typically used when the user was just created with a default
 * password to force the user to use its own password
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Only allow being here when it was forced by redirect
if (!Session::getUser()->getRedirect() or (Session::getUser()->getRedirect() !== (string) UrlBuilder::getWww('/force-password-update.html'))) {
    Response::redirect('prev', 302, reason_warning: tr('Force password update is only available when it was accessed using forced user redirect'));
}


// Validate sign in data and sign in
if (Request::isPostRequestMethod()) {
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
        Response::getFlashMessages()->addSuccessMessage(tr('Your password has been updated'));
        Response::redirect('prev');

    } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
        Response::getFlashMessages()->addWarningMessage(tr('Please specify at least ":count" characters for the password', [
            ':count' => Config::getInteger('security.passwords.size.minimum', 10)
        ]));

    } catch (ValidationFailedException $e) {
        Response::getFlashMessages()->addMessage($e);

    }catch (PasswordNotChangedException $e) {
        Response::getFlashMessages()->addWarningMessage(tr('You provided your current password. Please update your account to have a new and secure password'));
    }
}


// Set page meta data
Response::setPageTitle(tr('Please update your password before continuing...'));


// Render the page
echo ForcePasswordUpdateRequest::new()
    ->setEmail(Session::getUser()->getEmail())
    ->render();