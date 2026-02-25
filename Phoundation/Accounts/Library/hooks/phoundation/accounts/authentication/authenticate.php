<?php

/**
 * Hook authentication
 *
 * This is the default user authentication hook. This hook may be modified to change how the sign-in process works
 *
 * The hook must return a valid (and, ideally, you know, authenticated) UserInterface object or throw an
 * AuthenticationException on failure
 *
 * @see       AuthenticationException
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Hooks\Hook;


// Ensure we have the hook object
$_hook = Hook::ensure($_hook);


// Default setup: Use internal authentication
return User::authenticateInternal($_hook->getArgument('identifier'), $_hook->getArgument('password'), $_hook->getArgument('action'), $_hook->getArgument('domain'));
