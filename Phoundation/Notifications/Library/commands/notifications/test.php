<?php

/**
 * Command notifications test
 *
 * Notifications test script. This script will send test notifications to the specified users or roles
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notifications
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;


// Get arguments
$count = 0;
$argv  = ArgvValidator::new()
    ->select('-u,--users', true)->isOptional()->xor('roles')->hasMaxCharacters(2048)->sanitizeForceArray()->each()->isEmail()
    ->select('-r,--roles', true)->isOptional()->xor('users')->hasMaxCharacters(2048)->sanitizeForceArray()->each()->isVariable()
    ->validate();

if ($argv['users']) {
    foreach ($argv['users'] as $user) {
        $user = User::load($user);

        // Send the test notification to all specified users
        Notification::new()
            ->setMode(pick_random_argument(EnumDisplayMode::error, EnumDisplayMode::warning, EnumDisplayMode::success, EnumDisplayMode::info, EnumDisplayMode::notice))
            ->setUsersId($user->getId())
            ->setTitle(tr('This is a test notification'))
            ->setMessage(tr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'))
            ->setDetails(['test' => Strings::getRandom(16)])
            ->log()
            ->send();

        $count++;
    }

} else {
    // Send the test notification to all specified users
    Notification::new()
        ->setMode(pick_random_argument(EnumDisplayMode::error, EnumDisplayMode::warning, EnumDisplayMode::success, EnumDisplayMode::info, EnumDisplayMode::notice))
        ->setRoles($argv['roles'])
        ->setTitle(tr('This is a test notification'))
        ->setMessage(tr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'))
        ->setDetails(['test' => Strings::getRandom(16)])
        ->log()
        ->send();

    $count = count($argv['roles']);
}

Log::success(tr('Sent out ":count" test notifications', [':count' => $count]));
