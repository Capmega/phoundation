<?php

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Pages\SignUpPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


/**
 * Page sign-up
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
Request::execute('system/404');

// Only show sign-up page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Response::redirect('prev', 302);
}


// Get arguments
$get = GetValidator::new()
                   ->select('email')->isOptional()->isEmail()
                   ->validate();


// Validate sign in data and sign in
if (Request::isPostRequestMethod()) {
    try {
        $post = Session::validateSignUp();
        throw new UnderConstructionException();
        Response::redirect('prev');

    } catch (ValidationFailedException) {
        Request::getFlashMessages()->addWarningMessage(tr('Please specify a valid email and password'));
    } catch (AuthenticationException) {
        Request::getFlashMessages()->addWarningMessage(tr('The specified email and/or password were incorrect'));
    }
}


// Set page meta data
Response::setPageTitle(tr('Please sign in'));


// Render the sign-up page
echo SignUpPage::new()
               ->setEmail($post['email'])
               ->render();