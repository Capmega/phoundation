<?php

declare(strict_types=1);

/**
 * Command network sockets servers echo
 *
 * Creates and starts a local EchoServer which can be connected to with Telnet (manually) or with SocketClient class
 * For step-by-step instructions,
 * @see EchoServer
 *
 * @author Harrison Macey <harrison@medinet.ca>
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Scripts
 */

use Phoundation\Network\Sockets\EchoServer;

$server = new EchoServer('0.0.0.0'); // Start the EchoServer
