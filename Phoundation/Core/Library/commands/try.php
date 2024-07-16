<?php

/**
 * Script try
 *
 * General quick try and test script. Scribble any test code that you want to execute here and execute it with
 * ./pho try
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Accounts\Users\SystemUser;
use Phoundation\Accounts\Users\User;
use Phoundation\Utils\Strings;

show('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

$user = GuestUser::new();
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

$user = User::new($user->getId(), 'id');
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

$user = User::new('guest');
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

$user = User::new('guest', 'email');
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));





$user = SystemUser::new();
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

$user = User::new('system');
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

$user = User::new('system', 'email');
show($user->getLogId() . ' > ' . Strings::ensureVisible($user->getReadonly()));

