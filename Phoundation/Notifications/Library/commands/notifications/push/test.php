<?php

/**
 * Command notifications push test
 *
 * Push notifications test script. This script will send push notifications to the specified user
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
use Serhiy\Pushover\Api\Message\Attachment;
use Serhiy\Pushover\Api\Message\CustomSound;
use Serhiy\Pushover\Api\Message\Message;
use Serhiy\Pushover\Api\Message\Priority;
use Serhiy\Pushover\Api\Message\Sound;
use Serhiy\Pushover\Application;
use Serhiy\Pushover\Client\Response\MessageResponse;
use Serhiy\Pushover\Recipient;


// Get arguments
$count = 0;
$argv  = ArgvValidator::new()
    ->select('-u,--users'    , true)->isOptional()->xor('roles')->hasMaxCharacters(2048)->sanitizeForceArray()->each()->isEmail()
    ->select('-r,--roles'    , true)->isOptional()->xor('users')->hasMaxCharacters(2048)->sanitizeForceArray()->each()->isVariable()
    ->select('-e,--emergency', true)->isOptional()->xor('users')->hasMaxCharacters(2048)->sanitizeForceArray()->each()->isVariable()
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
    // Send the test notification to all specified roles
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













// instantiate pushover application and recipient of the notification (can be injected into service using Dependency Injection)
$application = new Application("replace_with_pushover_application_api_token");
$recipient = new Recipient("replace_with_pushover_user_key");


// if required, specify devices, otherwise  notification will be sent to all devices
$recipient->addDevice("android");
$recipient->addDevice("iphone");

// compose a message
$message = new Message("This is a test message", "This is a title of the message");
$message->setUrl("https://www.example.com");
$message->setUrlTitle("Example URL");
$message->setisHtml(false);
$message->setTimestamp(new \DateTime('now'));
$message->setTtl(60 * 60 * 24); // 1 day
// assign priority to the notification
$message->setPriority(new Priority(Priority::NORMAL));

// create notification
$notification = new \Serhiy\Pushover\Api\Message\Notification($application, $recipient, $message);
// set notification built-in sound
$notification->setSound(new Sound(Sound::PUSHOVER));
// or set notification custom sound
$notification->setCustomSound(new CustomSound("door_open"));
// add attachment
$notification->setAttachment(new Attachment("/path/to/file.jpg", Attachment::MIME_TYPE_JPEG));

// push notification
/** @var MessageResponse $response */
$response = $notification->push();

// work with response object
if ($response->isSuccessful()) {
    //...
}












Log::success(tr('Sent out ":count" test notifications', [':count' => $count]));
