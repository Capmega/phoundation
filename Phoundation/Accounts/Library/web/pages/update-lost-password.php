<?php

/**
 * Page update-lost-password
 *
 * This page allows users to update their lost password. It is typically used when the user lost their password and need
 * a new one. It requires them being signed in using a sign-in key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordNotChangedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Pages\LostPasswordUpdatedPage;
use Phoundation\Web\Html\Pages\UpdateLostPasswordPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Only allow being here when it was forced by redirect
if (Session::getUser()->isGuest()) {
    Response::redirect('prev', 302, reason_warning: tr('Update lost password page is only available to registered users'));
}

if (!Session::getSignInKey()) {
    Response::redirect('prev', 302, reason_warning: tr('Update lost password page is only available through sign-key sessions'));
}


// Update password
if (Request::isPostRequestMethod()) {
    try {
        // Validate password data
        $post = PostValidator::new()
                             ->select('password')->isPassword()
                             ->select('passwordv')->isEqualTo('password')
                             ->validate();

        // Update the password for this session user
        Session::getUser()->changePassword($post['password'], $post['passwordv'])->save();

        // Register a security incident
        Incident::new()
                ->setSeverity(Severity::medium)
                ->setType(tr('User lost password update'))
                ->setTitle(tr('The user ":user" updated their lost password using UUID key ":key"', [
                    ':key'  => Session::getSignInKey(),
                    ':user' => Session::getUser()->getLogId(),
                ]))
                ->setDetails([
                                 ':key'  => Session::getSignInKey(),
                                 ':user' => Session::getUser()->getLogId(),
                             ])
                ->save();

        // Add a flash message and redirect to the original target
        Response::getFlashMessages()->addSuccess(tr('Your password has been updated'));
        $updated = true;

    } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
        Response::getFlashMessages()->addWarning(tr('Please specify at least ":count" characters for the password', [
            ':count' => Config::getInteger('security.passwords.size.minimum', 10),
        ]));

    } catch (ValidationFailedException $e) {
        Response::getFlashMessages()->addMessage($e);

    } catch (PasswordNotChangedException $e) {
        Response::getFlashMessages()->addWarning(tr('You provided your current password. Please update your account to have a new and secure password'));
    }
}


// This page will build its own body
Response::setBuildBody(false);
if (isset($updated)) {
    // Set page meta data
    Response::setPageTitle(tr('Your password has been updated, please go to the sign-in page in to continue...'));


    // Render the page
    LostPasswordUpdatedPage::new()
                           ->setEmail(Session::getUser()->getEmail())
                           ->render();

} else {
    // Set page meta data
    Response::setPageTitle(tr('Please update your password before continuing...'));


    // Render the page
    UpdateLostPasswordPage::new()
                          ->setEmail(Session::getUser()->getEmail())
                          ->render();
}
