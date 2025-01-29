<?php

/**
 * Class PhoSocketServer
 *
 * A server with multiple PhoSocket objects and connections
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\Exception\SocketServerException;
use Phoundation\Utils\Config;
use Throwable;


class PhoSocketServer extends PhoSocketServerCore
{
    /**
     * Set up the configuration for the server
     *
     * @param string $address An IPv4, IPv6, or Unix socket address
     * @param int    $port
     * @param ?int   $timeout Seconds to wait on a socket before timing it out
     *
     * @throws SocketException
     */
    public function __construct(string $address, int $port = 0, ?int $timeout = 0)
    {
        try {
            $this->setListenAddress($address)
                 ->setListenPort($port)
                 ->setTimeout($timeout)
                 ->setUSleep(config()->getInteger('network.sockets.usleep', 10));

        } catch (Throwable $e) {
            throw SocketServerException::new($e->getMessage(), $e);
        }
    }


    /**
     * Returns a new PhoSocketServer object
     *
     * @param string   $address
     * @param int      $port
     * @param int|null $timeout
     *
     * @return static
     */
    public static function new(string $address, int $port = 0, ?int $timeout = 0): static
    {
        return new static($address, $port, $timeout);
    }
}
