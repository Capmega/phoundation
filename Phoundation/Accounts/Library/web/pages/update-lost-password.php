<?php

/**
 * Page update-lost-password
 *
 * This page allows users to update their lost password. It is typically used when the user lost their password and need
 * a new one. It requires them being signed in using a sign-in key
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordNotChangedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Pages\LostPasswordUpdatedPage;
use Phoundation\Web\Html\Pages\UpdateLostPasswordPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Only allow being here when it was forced by redirect
if (Session::getUserObject()->isGuest()) {
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
        $user = Session::getUserObject();

        if ($user->hasPassword($post['password'])) {
            // User used the same password, don't update
            Response::getFlashMessagesObject()->addSuccess(tr('You supplied your actual password, password was not updated'));
            $updated = false;

        } else {
            // Update the password
            $user->changePassword($post['password'], $post['passwordv'], true)
                 ->save();

            // Add a flash message and redirect to the original target
            Response::getFlashMessagesObject()->addSuccess(tr('Your password has been updated'));

            $updated = true;
        }

    } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
        Response::getFlashMessagesObject()->addWarning(tr('Please specify at least ":count" characters for the password', [
            ':count' => config()->getInteger('security.passwords.size.minimum', 10),
        ]));

    } catch (ValidationFailedException $e) {
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// This page will build its own body
Response::setRenderMainWrapper(false);

if (isset($updated)) {
    // Register a security incident
    Incident::new()
            ->setSeverity(EnumSeverity::medium)
            ->setType(tr('User lost password update'))
            ->setTitle(tr('User ":user" updated their lost password', [
                ':user' => Session::getUserObject()->getLogId(),
            ]))
            ->setBody(tr('The user ":user" updated their lost password using UUID key ":key"', [
                ':key'  => Session::getSignInKey(),
                ':user' => Session::getUserObject()->getLogId(),
            ]))
            ->setDetails([
                ':key'  => Session::getSignInKey(),
                ':user' => Session::getUserObject()->getLogId(),
            ])
            ->save();

    // Yay, the password was updated! Now, auto sign-in so that the user won't have to sign in manually
    Session::signIn($user->getEmail(), $post['password']);

    // Set page meta data
    Response::setPageTitle(tr('Your password has been updated, please go to the sign-in page in to continue...'));

    // Render the page
    return LostPasswordUpdatedPage::new()->setGetData([
        'email' => Session::getUserObject()->getEmail(),
    ]);

} else {
    // Set page meta data
    Response::setPageTitle(tr('Please update your password before continuing...'));

    // Render the page
    return UpdateLostPasswordPage::new()->setGetData([
        'email' => Session::getUserObject()->getEmail(),
    ]);
}
