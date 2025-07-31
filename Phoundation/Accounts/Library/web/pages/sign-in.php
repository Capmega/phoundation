<?php

/**
 * Page sign-in
 *
 * Displays a sign-in page, and allows a user to sign in to a user session
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Web\Html\Pages\SignInPage;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Only show sign-in page if we're a guest user
if (!Session::getUserObject()->isGuest()) {
    Response::redirect('prev', reason_warning: tr('Sign-in page is only available to guest users'));
}


// Is email specified by URL?
$get = GetValidator::new()
                   ->select('email')->isOptional()->isEmail()
                   ->select('redirect')->isOptional()->isUrl()
                   ->validate();


// Validate sign in data and sign in
if (Request::isPostRequestMethod()) {
    // Try to authenticate against UserRec first. If that fails, authenticate against User.
    foreach ([User::class] as $user_class) {
        try {
            $post = Session::validateSignIn();
            $user = Session::signIn($post['email'], $post['password'], $user_class);

            Session::redirectAfterSignIn($get['redirect'], $get['email']);

        } catch (AuthenticationException | PasswordTooShortException | NoPasswordSpecifiedException | ValidationFailedException $e) {
            Response::getFlashMessagesObject()->addWarning(tr('The specified email and/or password were incorrect'));
            $post = PostValidator::new()->getSource();
        }
    }
}


// Email might be specified by GET or POST
$get['email'] = isset_get($post['email']) ?? $get['email'];


// Display the sign-in page
return SignInPage::new()
                 ->setGetData($get)
                 ->setPostData(isset_get($post));
