<?php

/**
 * Page sign-in
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Pages\SignInPage;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

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

            Response::redirect(Url::getRedirect($redirect, $user->getDefaultPage()));

        } catch (PasswordTooShortException | NoPasswordSpecifiedException) {
            Response::getFlashMessagesObject()->addWarning(tr('Please specify at least ":count" characters for the password', [
                ':count' => Config::getInteger('security.passwords.size.minimum', 10),
            ]));

            break;

        } catch (ValidationFailedException $e) {
            Response::getFlashMessagesObject()->addWarning(tr('Please specify a valid email and password'));
            break;

        } catch (AuthenticationException $e) {
            Response::getFlashMessagesObject()->addWarning(tr('The specified email and/or password were incorrect'));
        }
    }

    if (empty($get['email'])) {
        GetValidator::new()->set(PostValidator::new()->get('email'), 'email');
    }
}


// Display the sign-in page
return SignInPage::new()->setGetData($get)->setPostData(isset_get($post));
