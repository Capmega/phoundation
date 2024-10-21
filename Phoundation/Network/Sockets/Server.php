<?php

namespace Phoundation\Network\Sockets;

use Error;
use Phoundation\Core\Core;
use Phoundation\Network\Sockets\Exception\SocketException;
use RuntimeException;

class Server
{
    /**
     * A Multi-dimensional array of callable arrays mapped by hook name.
     *
     * @var array<string, callable[]>
     */
    protected array $hooks = [];

    /**
     * IP Address.
     *
     * @var string
     */
    protected string $address;

    /**
     * Port Number.
     *
     * @var int
     */
    protected int $port;

    /**
     * Seconds to wait on a socket before timing out.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Domain.
     *
     * @see http://php.net/manual/en/function.socket-create.php
     *
     * @var int One of AF_INET, AF_INET6, AF_UNIX
     */
    protected int $domain;

    /**
     * The Master Socket.
     *
     * @var ?PhoSocket
     */
    protected ?PhoSocket $masterSocket = null;

    /**
     * Maximum Amount of Clients Allowed to Connect.
     *
     * @var int
     */
    protected int $maxClients = PHP_INT_MAX;

    /**
     * Maximum amount of characters to read in from a socket at once
     * This integer is passed directly to socket_read.
     *
     * @var int
     */
    protected int $maxRead = 1024;

    /**
     * Connected Clients.
     *
     * @var PhoSocket[]
     */
    protected array $clients = [];

    /**
     * Type of Read to use.  One of PHP_BINARY_READ, PHP_NORMAL_READ.
     *
     * @var int
     */
    protected int $readType = PHP_BINARY_READ;

    /**
     * Constant String for Generic Connection Hook.
     */
    public const HOOK_CONNECT = '__NAVARR_SOCKET_SERVER_CONNECT__';

    /**
     * Constant String for Generic Input Hook.
     */
    public const HOOK_INPUT = '__NAVARR_SOCKET_SERVER_INPUT__';

    /**
     * Constant String for Generic Disconnect Hook.
     */
    public const HOOK_DISCONNECT = '__NAVARR_SOCKET_SERVER_DISCONNECT__';

    /**
     * Constant String for Server Timeout.
     */
    public const HOOK_TIMEOUT = '__NAVARR_SOCKET_SERVER_TIMEOUT__';

    /**
     * Return value from a hook callable to tell the server not to run the other hooks.
     */
    public const RETURN_HALT_HOOK = false;

    /**
     * Return value from a hook callable to tell the server to halt operations.
     */
    public const RETURN_HALT_SERVER = '__NAVARR_HALT_SERVER__';


    /**
     * Setup the configuration for the server
     *
     * @param string $address An IPv4, IPv6, or Unix socket address
     * @param int $port
     * @param ?int $timeout Seconds to wait on a socket before timing it out
     * @throws SocketException
     */
    public function __construct(string $address, int $port = 0, ?int $timeout = 0)
    {
        try {
            $this->address = $address;
            $this->port    = $port;
            $this->timeout = $timeout;

            switch (true) {
                case filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4):
                    $this->domain = AF_INET;
                    break;

                case filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6):
                    $this->domain = AF_INET6;
                    break;

                default:
                    $this->domain = AF_UNIX;
            }

        } catch (\Throwable $e) {
            throw new SocketException($e->getMessage());
        }
    }

    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see run} you do not need to call this method.
     *
     * @throws SocketException
     */
    public function start(): void
    {
        try {
            Core::setTimeout(0);

            $this->masterSocket = PhoSocket::create($this->domain, SOCK_STREAM, 0)
                                           ->bind($this->address, $this->port)
                                           ->getSockName($this->address, $this->port)
                                           ->listen();

        } catch (\Throwable $e) {
            throw new SocketException($e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->shutDownEverything();
    }

    /**
     * Run the Server for as long as loopOnce returns true.
     *
     * @throws SocketException
     * @see loopOnce
     */
    public function run(): void
    {
        try {
            $this->ensureMasterSocketStarted();

            while ($this->loopOnce()) {
                usleep($this->usleep);
            }

            $this->shutDownEverything();

        } catch (\Throwable $e) {
            throw new SocketException($e->getMessage());
        }
    }


    /**
     * Ensure the Master Socket is started
     *
     * @return void
     */
    protected function ensureMasterSocketStarted(): void
    {
        if ($this->masterSocket === null) {
            $this->start();
        }
    }

    /**
     * This is the main server loop.  This code is responsible for adding connections and triggering hooks.
     *
     * @return bool Whether or not to shutdown the server
     * @throws SocketException
     */
    protected function loopOnce(): bool
    {
        try {
            $this->validateSocket();

            // Get all the sockets to read from
            $read   = array_merge([$this->masterSocket], $this->clients);
            $write  = [];
            $except = [];

            $numSelected = PhoSocket::select($read, $write, $except, $this->timeout);

            // Handle timeout scenario
            if ($this->timeout !== null && $numSelected === 0) {
                if ($this->triggerHooks(self::HOOK_TIMEOUT, $this->masterSocket) === false) {
                    return false; // Exit if hook tells server to shut down
                }
            }

            // Accept new connections
            if ($this->masterSocket && in_array($this->masterSocket, $read)) {
                $this->acceptNewConnection($read);
            }

            // Handle input from clients
            foreach ($read as $client) {
                if (!$this->handleClientInput($client)) {
                    return false; // Exit if hook tells server to shut down
                }
            }

            // Clean up
            unset($read, $write, $except);

        } catch (\Throwable $e) {
            throw new SocketException($e->getMessage());
        }

        return true; // Continue the loop
    }


    /**
     * Check if the master socket is started, throw exception if now
     *
     * @return void
     */
    protected function validateSocket(): void
    {
        if ($this->masterSocket === null) {
            throw SocketException::new(tr('Socket must be started before running server loop'));
        }
    }


    /**
     * Accepts a new connection in the Master Socket
     *
     * @param array $read
     *
     * @return void
     */
    protected function acceptNewConnection(array &$read): void
    {
        unset($read[array_search($this->masterSocket, $read)]);

        $socket = $this->masterSocket->accept();
        $this->clients[] = $socket;

        if ($this->triggerHooks(self::HOOK_CONNECT, $socket) === false) {
            return; // Exit if hook tells server to shut down
        }
    }


    /**
     * Handle Client Input
     *
     * @param $client
     *
     * @return bool
     */
    protected function handleClientInput($client): bool
    {
        $input = $this->read($client);

        if ($input === '') {
            return $this->disconnect($client); // Handle disconnect
        }

        return $this->triggerHooks(self::HOOK_INPUT, $client, $input) !== false; // Handle input
    }


    /**
     * Overrideable Read Functionality.
     *
     * @param PhoSocket $client
     *
     * @return string
     */
    protected function read(PhoSocket $client): string
    {
        try {
            $return = $client->read($this->maxRead, $this->readType);

        } catch (\Throwable $e) {
            throw new SocketException($e->getMessage());
        }

        return $return;
    }


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return bool Whether or not to continue running the server (true: continue, false: shutdown)
     */
    public function disconnect(PhoSocket $client, string $message = ''): bool
    {
        $clientIndex = array_search($client, $this->clients, true);

        if ($clientIndex === false) {
            return false;
        }

        $this->triggerHooks(self::HOOK_DISCONNECT, $this->clients[$clientIndex], $message);

        $this->clients[$clientIndex]->close();
        unset($this->clients[$clientIndex], $client);

        return true;
    }


    /**
     * Triggers the hooks for the supplied command.
     *
     * @param string      $command Hook to listen for (e.g. HOOK_CONNECT, HOOK_INPUT, HOOK_DISCONNECT, HOOK_TIMEOUT)
     * @param PhoSocket   $client
     * @param string|null $input   Message Sent along with the Trigger
     *
     * @return bool Whether or not to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerHooks(string $command, PhoSocket $client, ?string $input = null): bool
    {
        if (isset($this->hooks[$command])) {
            foreach ($this->hooks[$command] as $callable) {
                $continue = $callable($this, $client, $input);

                if ($continue === self::RETURN_HALT_HOOK) {
                    break;
                }
                if ($continue === self::RETURN_HALT_SERVER) {
                    return false;
                }
                unset($continue);
            }
        }

        return true;
    }


    /**
     * Attach a Listener to a Hook.
     *
     * @param string   $command  Hook to listen for
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addHook(string $command, callable $callable): static
    {
        if (empty($this->hooks[$command])) {
            $this->hooks[$command] = [];
        }

        $this->hooks[$command][] = $callable;
        return $this;
    }


    /**
     * Remove the provided Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeHook(string $command, callable $callable): static
    {
        if (isset($this->hooks[$command])) {
            if (in_array($callable, $this->hooks[$command])) {
                $hook = array_search($callable, $this->hooks[$command]);
                unset($this->hooks[$command][$hook], $hook);
            }
        }

        return $this;
    }


    /**
     * Disconnect all the Clients and shut down the server.
     *
     * @return void
     */
    private function shutDownEverything(): void
    {
        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }

        try {
            $this->masterSocket?->close();

        } catch (\Throwable $e) {
            // TODO: writer says this is needed to "catch harmless error"
            if (!str_contains($e->getMessage(), 'must not be accessed before initialization')) {
                throw new SocketException($e->getMessage());
            }
        }

        unset(
            $this->hooks,
            $this->address,
            $this->port,
            $this->timeout,
            $this->domain,
            $this->masterSocket,
            $this->maxClients,
            $this->maxRead,
            $this->clients,
            $this->readType
        );
    }
}