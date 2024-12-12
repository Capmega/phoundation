<?php

/**
 * Class EchoServer
 *
 * A test class to show the functionality of PhoSocketServer.
 * Everything the client sends to the EchoServer will be echoed back.
 *
 * To start, follow these steps:
 *
 * Choose a DEFAULT_PORT value
 * Include in a runnable php file: '$server = new EchoServer('0.0.0.0');'
 * Run the PHP file
 * in bash, do 'telnet 127.0.0.1 4096' but replace 4096 with you DEFAULT_PORT value
 * you are now connected, anything you type in the telnet window will be echoed back to you
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Core\Log\Log;

class EchoServer extends PhoSocketServer
{
    /**
     * This is the port used for the server
     */
    const int DEFAULT_PORT = 4096;


    /**
     * Constructs a new instance of an EchoServer object
     *
     * @param $ip
     * @param $port
     */
    public function __construct($ip = null, $port = self::DEFAULT_PORT)
    {
        parent::__construct($ip, $port);

        Log::action(tr('Awaiting Connection'));

        $this->addHook(PhoSocketServer::HOOK_CONNECT   , [$this, 'onConnect']);
        $this->addHook(PhoSocketServer::HOOK_INPUT     , [$this, 'onInput']);
        $this->addHook(PhoSocketServer::HOOK_DISCONNECT, [$this, 'onDisconnect']);
        $this->execute();
    }


    /**
     * Callback function that will be called when there is a new connection to the EchoServer
     *
     * @param PhoSocketServer $server
     * @param PhoSocket       $client
     * @param                 $message
     *
     * @return void
     */
    public function onConnect(PhoSocketServer $server, PhoSocket $client, $message): void
    {
        Log::action(tr('Connection Established'));
    }


    /**
     * Callback function that will be called when the EchoServer receives an input
     *
     * @param PhoSocketServer $server
     * @param PhoSocket       $client
     * @param string          $message
     *
     * @return void
     */
    public function onInput(PhoSocketServer $server, PhoSocket $client, string $message): void
    {
        $message = trim($message);

        Log::success(tr('Received ":message", length: ":length"', [
            ":message" => $message,
            ":length" => strlen($message)
        ]));

        switch ($message) {
            case 'QUIT':
                exit();
        }

        $response = $message;

        Log::action(tr('Responding with ":message"', [
            ":message" => $response]));

        $client->write($response);
    }


    /**
     * Callback function that will be called when disconnected from
     *
     * @param PhoSocketServer $server
     * @param PhoSocket       $client
     * @param                 $message
     *
     * @return void
     */
    public function onDisconnect(PhoSocketServer $server, PhoSocket $client, $message): void
    {
        Log::action(tr('Disconnection'));
    }
}


