<?php

/**
 * Page sign-key
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Exception\SignInKeyDifferentUserException;
use Phoundation\Accounts\Users\Exception\SignInKeyExpiredException;
use Phoundation\Accounts\Users\Exception\SignInKeyStatusException;
use Phoundation\Accounts\Users\Exception\SignInKeyUsedException;
use Phoundation\Accounts\Users\SignInKey;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Get the UUID key
$get = GetValidator::new()
                   ->select('id')->isUuid()
                   ->validate();


try {
    SignInKey::new()->load(['uuid' => $get['id']])->execute();

} catch (SignInKeyUsedException $e) {
    // Show authentication failed but add a message
    Request::executeSystem(410, message: tr('This direct login link has already been used, please request a new link'));

} catch (SignInKeyDifferentUserException $e) {
    // Show authentication failed but add a message
    Request::executeSystem(401, message: tr('This link is for a different user'));

} catch (SignInKeyExpiredException $e) {
    // Show authentication failed but add a message
    Request::executeSystem(401, message: tr('This link expired, please request a new link'));

} catch (SignInKeyStatusException $e) {
    // Show authentication failed but add a message
    Request::executeSystem(401, message: tr('This link is no longer valid, please request a new link'));

} catch (DataEntryNotExistsException $e) {
    // Yeaaaaahh, this key does not exist. Bye bye now!
    Request::executeSystem(401);
}
