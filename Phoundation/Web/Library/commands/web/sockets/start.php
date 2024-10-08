<?php

declare(strict_types=1);

use Phoundation\Web\Sockets\Test;
use Ratchet\Server\IoServer;


/**
 * Script web sockets start
 *
 * Starts PHP sockets server
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

$server = IoServer::factory(
    new Test(),
    8080
);

$server->run();