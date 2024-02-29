<?php

use Phoundation\Accounts\Users\Exception\SignInKeyDifferentUserException;
use Phoundation\Accounts\Users\Exception\SignInKeyExpiredException;
use Phoundation\Accounts\Users\Exception\SignInKeyStatusException;
use Phoundation\Accounts\Users\Exception\SignInKeyUsedException;
use Phoundation\Accounts\Users\SignInKey;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Page;
use Phoundation\Web\Routing\Route;


/**
 * Page sign-key
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get the UUID key
$get = GetValidator::new()
    ->select('id')->isUuid()
    ->validate();

try {
    SignInKey::get($get['id'], 'uuid')->execute();

} catch (SignInKeyUsedException $e) {
    // Show authentication failed but add a message
    Route::executeSystem(401, tr('This link has already been used, please request a new link'));

} catch (SignInKeyDifferentUserException $e) {
    // Show authentication failed but add a message
    Route::executeSystem(401, tr('This link is for a different user'));

} catch (SignInKeyExpiredException $e) {
    // Show authentication failed but add a message
    Route::executeSystem(401, tr('This link expired, please request a new link'));

} catch (SignInKeyStatusException $e) {
    // Show authentication failed but add a message
    Route::executeSystem(401, tr('This link is no longer valid, please request a new link'));

} catch (DataEntryNotExistsException $e) {
    // Yeaaaaahh, this key does not exist. Bye bye now!
    Route::executeSystem(401);
}
