<?php

use Phoundation\Core\Strings;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Page;



try {
    Notification::new()
        ->setMode(pick_random('UNKNOWN', 'ERROR', 'WARNING', 'SUCCESS', 'INFORMATION'))
        ->setRoles('developer')
        ->setTitle(tr('This is a test notification'))
        ->setMessage(tr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'))
        ->setDetails([
            'test' => Strings::random(16)
        ])
        ->log()
        ->send();
} catch (Throwable $e) {
    showdie($e);
}


// Redirect to the all notifications page
Page::redirect('notifications/all.html');