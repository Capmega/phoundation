<?php

/**
 * Test class
 *
 * This is a socket test class to experiment with PHP sockets
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Sockets;

use Exception;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;


class Test implements MessageComponentInterface
{
    protected $clients;


    /**
     * Test class constructor
     */
    public function __construct()
    {
        // Disable Core error handling as Ratchet is old and not PHP8 compatible
        Core::setErrorHandling(false);
        $this->clients = new SplObjectStorage;
    }


    /**
     *
     *
     * @param ConnectionInterface $conn
     *
     * @return void
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        Log::notice(tr('Opened web socket connection from ":ip"', [
            ':ip' => $conn->remoteAddress,
        ]), 6);
    }


    /**
     *
     *
     * @param ConnectionInterface $from
     * @param                     $msg
     *
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }


    /**
     *
     *
     * @param ConnectionInterface $conn
     *
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }


    /**
     *
     *
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     *
     * @return void
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
