<?php

/**
 * Ajax system/notifications/get.php
 *
 * This ajax call will return the contents of the specified notifications id
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;


// Validate the ID
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->validate();
// Update notification status to READ and return it
Json::new()->reply(Notification::load($get['id'])
                        ->setStatus('READ')
                        ->__toArray());
