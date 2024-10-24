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
     * @param int $length
     *
     * @return string
     */
    public function receive(int $length = 1024): string
    {
        return $this->socket->read($length);
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
}