<?php

namespace Phoundation\Network\Sockets;

class EchoServer extends SocketServer
{
    const DEFAULT_PORT = 4097;

    public function __construct($ip = null, $port = self::DEFAULT_PORT)
    {
        parent::__construct($ip, $port);
        $this->addHook(SocketServer::HOOK_CONNECT, array($this, 'onConnect'));
        $this->addHook(SocketServer::HOOK_INPUT, array($this, 'onInput'));
        $this->addHook(SocketServer::HOOK_DISCONNECT, array($this, 'onDisconnect'));
        $this->run();
    }

    public function onConnect(SocketServer $server, PhoSocket $client, $message)
    {
        echo 'Connection Established',"\n";
    }

    public function onInput(SocketServer $server, PhoSocket $client, $message)
    {
        $message = trim($message);

        if ($message === 'QUIT') {
            die();
        }

        echo 'Received "',$message,'"',"\n";
        $client->write($message, strlen($message));
    }

    public function onDisconnect(SocketServer $server, PhoSocket $client, $message)
    {
        echo 'Disconnection',"\n";
    }
}

$server = new EchoServer('0.0.0.0');
