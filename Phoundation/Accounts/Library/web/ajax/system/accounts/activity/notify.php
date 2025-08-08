<?php

/**
 * AJAX REST request system/accounts/activity/notify
 *
 * This request will update the users last_activity value to what was specified
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Json\AutoSuggestRequest;
use Phoundation\Web\Requests\JsonPage;


// Ensure we'll have auto suggest data
AutoSuggestRequest::init(true, false);


// No GET variables allowed
GetValidator::new()->validate();


// Validate post
$post = PostValidator::new()
                     ->select('time')->isTimestamp(true)->isLessThan(Session::getAutoSignOut())
                     ->validate();


// Update the last_activity
Session::updateLastActivityTimestamp($post['time'], true);


// Reply!
JsonPage::new()->reply([
    'auto-sign-out' => (int) floor(Session::getAutoSignOutTimeLeft())
]);
