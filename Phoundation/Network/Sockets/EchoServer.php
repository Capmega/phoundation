<?php

namespace Phoundation\Network\Sockets;

class EchoServer extends Server
{
    const DEFAULT_PORT = 4098;

    public function __construct($ip = null, $port = self::DEFAULT_PORT)
    {
        parent::__construct($ip, $port);
        $this->addHook(Server::HOOK_CONNECT   , [$this, 'onConnect']);
        $this->addHook(Server::HOOK_INPUT     , [$this, 'onInput']);
        $this->addHook(Server::HOOK_DISCONNECT, [$this, 'onDisconnect']);
        $this->run();
    }

    public function onConnect(Server $server, PhoSocket $client, $message)
    {
        echo 'Connection Established',"\n";
    }

    public function onInput(Server $server, PhoSocket $client, $message)
    {
        $message = trim($message);

        if ($message === 'QUIT') {
            die();
        }

        $response = 'Echoing: ' . $message . "\n";

        echo 'Received "',$message,'"',"\n";
        $client->write($response, strlen($response));
    }

    public function onDisconnect(Server $server, PhoSocket $client, $message)
    {
        echo 'Disconnection',"\n";
    }
}

$server = new EchoServer('0.0.0.0');
