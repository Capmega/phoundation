<?php

/**
 * Class Client
 *
 * A client that will connect to a PhoSocketServer and simulate communication.
 * Will accomplish the same thing as manually connecting through telnet and communicating.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Utils\Config;

class PhoClient
{
    /**
     * @var PhoSocket The PhoSocket that this Client will use to connect to the server
     */
    protected PhoSocket $socket;

    /**
     * The Maximum number of characters to send at once. This integer is passed directly to socket_read.
     *
     * @var int
     */
    protected int $max_send = 1024;


    /**
     * PhoClient class constructor
     *
     * @param string $ip
     * @param int    $port
     *
     * @see PhoSocket::create()
     */
    public function __construct(string $ip, int $port)
   {
       $this->socket = PhoSocket::create(EnumNetworkSocketDomain::AF_INET, SOCK_STREAM, SOL_TCP)
                                ->setRemoteAddress($ip)
                                ->setRemotePort($port);
   }


    /**
     * Returns a new static object
     *
     * @param string $ip
     * @param int    $port
     *
     * @return static
     */
    public static function new(string $ip, int $port): static
    {
        return new static($ip, $port);
    }


    /**
     * Returns a new static object
     *
     * @param string $configuration_path
     *
     * @return static
     */
    public static function newFromConfiguration(string $configuration_path): static
    {
        $configuration = config()->getArray($configuration_path, require_keys: ['address', 'port', 'listen_port']);

        return new static($configuration['address'], $configuration['port'] ?? $configuration['listen_port']);
    }


    /**
     * Connects the client to the specified server
     *
     * @return PhoClient
     */
    public function connect(): static
    {
        $this->socket->connect();
        return $this;
    }


    /**
     * Sends a message to the server
     *
     * @param string $message
     *
     * @return $this
     */
    public function send(string $message): static
    {
        $this->socket->write($message);
        return $this;
    }


    /**
     * Receives data from the server
     *
     * @return string
     */
    public function receive(): string
    {
        return $this->socket->read($this->max_send);
    }


    /**
     * Closes the connection
     *
     * @return $this
     */
    public function close(): static
    {
        $this->socket->disconnect();
        return $this;
    }


    /**
     * Returns the max_send value of this Client object
     *
     * @return int
     */
    public function getMaxSend(): int
    {
        return $this->max_send;
    }


    /**
     * Sets the max_send value of this Client object, returns static
     *
     * @param int $max_send
     *
     * @return static
     */
    public function setMaxSend(int $max_send): static
    {
        $this->max_send = $max_send;
        return $this;
    }


    /**
     * Returns the PhoSocket object for this Client object
     *
     * @param PhoSocket $socket
     */
    protected function getSocket(PhoSocket $socket): void
    {
        $this->socket = $socket;
    }


    /**
     * Sets the PhoSocket object for this Client object
     *
     * @param PhoSocket $socket
     *
     * @return PhoClient
     */
    protected function setSocket(PhoSocket $socket): static
    {
        $this->socket = $socket;
        return $this;
    }
}
