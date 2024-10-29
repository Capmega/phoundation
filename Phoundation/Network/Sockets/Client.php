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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;


class Client
{
    /**
     * @var PhoSocket The PhoSocket that this Client will use to connect to the server
     */
    protected PhoSocket $socket;

    /**
     * Maximum amount of characters to send at once
     * This integer is passed directly to socket_read.
     *
     * @var int
     */
    protected int $max_send = 1024;


    /**
     * Constructs a Client object by creating a socket and connecting the socket to the specified server
     *
     * @param string $ip
     * @param int    $port
     *
     * @see PhoSocket::create()
     */
    public function __construct(string $ip, int $port)
   {
       $this->socket = PhoSocket::create(AF_INET, SOCK_STREAM, SOL_TCP);
       $this->connect($ip, $port);
   }


    /**
     * Connects the client to the specified server
     *
     * @param string $ip
     * @param int    $port
     *
     * @return Client
     */
    public function connect(string $ip, int $port): static
    {
        show("client created");
        $this->socket->connect($ip, $port);
        return $this;
    }


    /**
     * Sends a message (string) to the server
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
        $this->socket->close();
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
     * @returns static
     */
    protected function setSocket(PhoSocket $socket): static
    {
        $this->socket = $socket;
        return $this;
    }
}