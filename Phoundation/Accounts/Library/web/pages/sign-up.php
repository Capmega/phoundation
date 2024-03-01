<?php

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Pages\SignUpPage;
use Phoundation\Web\Page;


/**
 * Page sign-up
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
Page::execute('system/404');

// Only show sign-up page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Page::redirect('prev', 302);
}


// Get arguments
$get = GetValidator::new()
    ->select('email')->isOptional()->isEmail()
    ->validate();


// Validate sign in data and sign in
if (Page::isPostRequestMethod()) {
    try {
        $post = Session::validateSignUp();
throw new UnderConstructionException();
        Page::redirect('prev');

    } catch (ValidationFailedException) {
        Page::getFlashMessages()->addWarningMessage(tr('Please specify a valid email and password'));
    } catch (AuthenticationException) {
        Page::getFlashMessages()->addWarningMessage(tr('The specified email and/or password were incorrect'));
    }
}


// Set page meta data
Page::setPageTitle(tr('Please sign in'));


// Render the sign-up page
echo SignUpPage::new()
    ->setEmail($post['email'])
    ->render();