<?php

/**
 * Class PhoSocketServerCore
 *
 * Core functionality for PhoSocketServer
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataUSleep;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Network\Sockets\Exception\SocketDisconnectionException;
use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\Exception\SocketServerException;
use Phoundation\Network\Sockets\Interfaces\PhoSocketServerInterface;
use Phoundation\Security\Incidents\Incident;
use Throwable;


class PhoSocketServerCore implements PhoSocketServerInterface
{
    use TraitDataUSleep;


    /**
     * Constant String for Generic Connection Hook.
     */
    public const string HOOK_CONNECT = '__SOCKET_SERVER_CONNECT__';

    /**
     * Constant String for Generic Input Hook.
     */
    public const string HOOK_INPUT = '__SOCKET_SERVER_INPUT__';

    /**
     * Constant String for Generic Disconnect Hook.
     */
    public const string HOOK_DISCONNECT = '__SOCKET_SERVER_DISCONNECT__';

    /**
     * Constant String for Server Timeout.
     */
    public const string HOOK_TIMEOUT = '__SOCKET_SERVER_TIMEOUT__';

    /**
     * Return value from a hook callable to tell the server not to run the other hooks.
     */
    public const bool RETURN_HALT_HOOK = false;

    /**
     * Return value from a hook callable to tell the server to halt operations.
     */
    public const string RETURN_HALT_SERVER = '__HALT_SERVER__';

    /**
     * An array of hooks to be called when a socket connects to this server
     *
     * @var array<string, callable[]>
     */
    protected array $connection_hooks = [];

    /**
     * An array of hooks to be called when a socket sends a message to this server
     *
     * @var array<string, callable[]>
     */
    protected array $input_hooks = [];

    /**
     * An array of hooks to be called when a socket disconnects from this server
     *
     * @var array<string, callable[]>
     */
    protected array $disconnection_hooks = [];

    /**
     * An array of hooks to be called when a socket disconnects from this server
     *
     * @var array<string, callable[]>
     */
    protected array $timeout_hooks = [];

    /**
     * IP Address.
     *
     * @var string
     */
    protected string $local_address;

    /**
     * Port Number.
     *
     * @var int
     */
    protected int $local_port;

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
    protected ?PhoSocket $master_socket = null;

    /**
     * Maximum Amount of Clients Allowed to Connect.
     *
     * @var int
     */
    protected int $max_clients = PHP_INT_MAX;

    /**
     * Maximum amount of characters to read in from a socket at once
     * This integer is passed directly to socket_read.
     *
     * @var int
     */
    protected int $max_read = 1024;

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
    protected int $read_type = PHP_BINARY_READ;

    /**
     * Tracks the total amount of messages received
     *
     * @var int $total_count
     */
    protected int $total_count = 0;

    /**
     * Tracks the amount of messages sent within the last second
     *
     * @var int $second_count
     */
    protected int $second_count = 0;

    /**
     * Tracks the average amount of messages per second
     *
     * @var float $second_average
     */
    protected float $second_average = 0;

    /**
     * Tracks the amount of seconds that this socket server has been listening
     *
     * @var int $total_seconds
     */
    protected int $total_seconds = 0;

    /**
     * Tracks statistics time
     *
     * @var float $time
     */
    protected float $time = 0;


    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see execute} you do not need to call this method.
     *
     * @throws SocketException
     */
    public function start(): static
    {
        Core::setTimeout(0);

        $this->master_socket = PhoSocket::create($this->domain, SOCK_STREAM, 0)
                                        ->bind($this->local_address, $this->local_port)
                                        ->getSockName($this->local_address, $this->local_port)
                                        ->listen();

        return $this;
    }


    /**
     * Called when object destroyed
     */
    public function __destruct()
    {
        $this->disconnectEverything();
    }


    /**
     * Checks if the master socket is started,
     *
     * @return $this
     */
    public function ensureMasterSocketStarted(): static
    {
        if ($this->master_socket === null) {
            $this->start();
        }

        return $this;
    }


    /**
     * Updates internal statistics
     *
     * @return void
     */
    protected function updateStatistics(): void
    {
        $time = microtime(true);

        if (($time - $this->time) > 1) {
            // 1 Second passed, update statistics
            $this->second_average = ($this->total_count / ++$this->total_seconds);

            $this->second_count = 0;
            $this->time = $time;
        }
    }


    public function getOnlineSeconds(): int
    {
        throw UnderConstructionException::new();
    }


    /**
     * Returns the total count of messages received
     *
     * @return int
     */
    public function getTotalMessages(): int
    {
        return $this->total_count;
    }


    /**
     * Returns the number of messages received this second
     *
     * @return int
     */
    public function getMessagesThisSecond(): int
    {
        throw UnderConstructionException::new();
    }


    /**
     * Returns the average number of messages received per second based on the total time elapsed and the
     * total message count
     *
     * @return float
     */
    public function getMessagesPerSecond(): float
    {
        throw UnderConstructionException::new();
    }


    /**
     * Run the Server for as long as server iteration returns true.
     *
     * @param bool $exception
     *
     * @throws SocketException
     */
    public function execute(bool $exception = false): void
    {
        try{
            $this->ensureMasterSocketStarted();

            while ($this->processServerIteration($exception)) {
                $this->updateStatistics();
                usleep($this->usleep);
            }

            $this->disconnectEverything();

        } catch (Throwable $e) {
            throw SocketServerException::new($e->getMessage(), $e);
        }
    }


    /**
     * Processes server operations for one iteration of the main loop, including handling new connections and
     * client inputs.
     *
     * @return bool Returns false if the server should shut down, true otherwise.
     *
     * @throws SocketException
     */
    protected function processServerIteration(bool $exception = false): bool
    {
        try {
            $this->validateSocket();

            $write_sockets     = [];
            $except_sockets    = [];
            $read_sockets      = array_merge([$this->master_socket], $this->clients);
            $num_ready_sockets = PhoSocket::select($read_sockets, $write_sockets, $except_sockets, $this->timeout);

            if (($this->timeout !== null) and ($num_ready_sockets === 0)) {
                if ($this->triggerTimeoutHooks($this->master_socket) === false) {
                    return false;
                }
            }

            if (($this->master_socket) and (in_array($this->master_socket, $read_sockets))) {
                $this->acceptNewConnection($read_sockets);
            }

            foreach ($read_sockets as $client) {
                if (!$this->handleClientInput($client)) {
                    return false;
                }
            }

            unset($read_sockets, $write_sockets, $except_sockets);

        } catch (Throwable $e) {
            if ($exception) {
                throw SocketServerException::new($e->getMessage(), $e);
            }

            Incident::new()
                    ->setException($e)
                    ->setLog(ENVIRONMENT === 'production' ? 10 : 4)
                    ->setNotifyRoles('developer')
                    ->save();
        }

        return true;
    }


    /**
     * Check if the master socket is started, throw exception if now
     *
     * @return void
     */
    protected function validateSocket(): void
    {
        if ($this->master_socket === null) {
            throw SocketServerException::new(tr('Socket must be started before running server loop'));
        }
    }


    /**
     * Accepts a new connection in the master socket
     *
     * @param array $read
     *
     * @return void
     */
    protected function acceptNewConnection(array &$read): void
    {
        unset($read[array_search($this->master_socket, $read)]);

        $socket          = $this->master_socket->accept();
        $this->clients[] = $socket;

        if ($this->triggerConnectionHooks($socket) === false) {
            usleep($this->usleep);
        }
    }


    /**
     * Handles client input
     *
     * @param $client
     *
     * @return bool
     */
    protected function handleClientInput($client): bool
    {
        $input = $this->read($client);

        if ($input === '') {
            $this->disconnect($client);
            return true;
        }

        return $this->triggerInputHooks($client, $input);
    }


    /**
     * Read functionality.
     *
     * @param PhoSocket $client
     *
     * @return string
     */
    protected function read(PhoSocket $client): string
    {
        return $client->read($this->max_read, $this->read_type);
    }


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void
     */
    public function disconnect(PhoSocket $client, string $message = ''): void
    {
        $client_index = array_search($client, $this->clients, true);

        if ($client_index === false) {
            return;
        }

        $this->triggerDisconnectionHooks($this->clients[$client_index]);
        $this->clients[$client_index]->close();

        unset($this->clients[$client_index], $client);
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket sends a message to this PhoSocketServer
     *
     * @param PhoSocket   $client
     * @param string|null $input Message Sent along with the Trigger
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerInputHooks(PhoSocket $client, ?string $input = null): bool
    {
        foreach ($this->input_hooks as $callable) {
            $method = $callable($this, $client, $input);
            unset($method);
        }

        return true;
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket connects to this PhoSocketServer
     *
     * @param PhoSocket $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerConnectionHooks(PhoSocket $client): bool
    {
        $this->total_count++;

        foreach ($this->connection_hooks as $callable) {
            $method = $callable($this, $client);
            unset($method);
        }

        return true;
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket disconnects from this PhoSocketServer
     *
     * @param PhoSocket $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerDisconnectionHooks(PhoSocket $client): bool
    {
        foreach ($this->disconnection_hooks as $callable) {
            $method = $callable($this, $client);

            unset($method);
        }

        return true;
    }


    /**
     * Triggers the hooks in the timeout_hooks array.
     *
     * @param PhoSocket $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerTimeoutHooks(PhoSocket $client): bool
    {
        foreach ($this->timeout_hooks as $callable) {
            $method = $callable($this, $client);

            if ($method === static::RETURN_HALT_HOOK) {
                break;
            }

            // TODO: finalize this
            if ($method === static::RETURN_HALT_SERVER) {
                return false;
            }

            unset($method);
        }

        return true;
    }


    /**
     * Attach a Callback function that will be called when a connection is initiated
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addConnectionHook(callable $callable): static
    {
        $this->connection_hooks[] = $callable;
        return $this;
    }


    /**
     * Attach a Callback function that will be called when a connection is terminated
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addDisconnectionHook(callable $callable): static
    {
        $this->disconnection_hooks[] = $callable;
        return $this;
    }


    /**
     * Attach a Callback function that will be called when a message is sent from the PhoSocket client
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addInputHook(callable $callable): static
    {
        $this->input_hooks[] = $callable;
        return $this;
    }


    /**
     * Attach a Callback function that will be called when a connection is timed out
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addTimeoutHook(callable $callable): static
    {
        $this->timeout_hooks[] = $callable;
        return $this;
    }


    /**
     * Remove the provided Connection Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeConnectionHook(string $command, callable $callable): static
    {
        if (in_array($callable, $this->connection_hooks)) {
            $hook = array_search($callable, $this->connection_hooks);

            unset($this->connection_hooks[$hook], $hook);
        }

        return $this;
    }


    /**
     * Remove the provided Disconnection Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeDisconnectionHook(string $command, callable $callable): static
    {
        if (in_array($callable, $this->disconnection_hooks)) {
            $hook = array_search($callable, $this->disconnection_hooks);

            unset($this->disconnection_hooks[$hook], $hook);
        }

        return $this;
    }


    /**
     * Remove the provided Input Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeInputHook(string $command, callable $callable): static
    {
        if (in_array($callable, $this->input_hooks)) {
            $hook = array_search($callable, $this->input_hooks);

            unset($this->input_hooks[$hook], $hook);
        }

        return $this;
    }


    /**
     * Remove the provided Timeout Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeTimeoutHook(string $command, callable $callable): static
    {
        if (in_array($callable, $this->timeout_hooks)) {
            $hook = array_search($callable, $this->timeout_hooks);

            unset($this->timeout_hooks[$hook], $hook);
        }

        return $this;
    }


    /**
     * Disconnect all the Clients and shut down the server.
     *
     * @return void
     */
    protected function disconnectEverything(): void
    {
        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }

        $this->closeMasterSocket();

        unset(
            $this->hooks,
            $this->local_address,
            $this->local_port,
            $this->timeout,
            $this->domain,
            $this->masterSocket,
            $this->max_clients,
            $this->max_read,
            $this->clients,
            $this->read_type
        );
    }


    /**
     * Closes the master socket for this PhoSocketServer object
     *
     * @return $this
     */
    protected function closeMasterSocket(): static
    {
        try {
            if (isset($this->master_socket)) {
                $this->master_socket->shutdown();
                $this->master_socket->close(0, true);
            }

        } catch (Throwable $e) {
            // Ignore error that occurs when master_socket is never initialized
            if (!str_contains($e->getMessage(), 'must not be accessed before initialization')) {
                throw SocketServerException::new($e->getMessage());
            }
        }

        return $this;
    }


    /**
     * Returns the address property of this PhoSocketServer
     *
     * @return string
     */
    public function getLocalAddress(): string
    {
        return $this->local_address;
    }


    /**
     * Sets the address property of this PhoSocketServer
     *
     * @param $local_address
     *
     * @return static
     */
    public function setLocalAddress($local_address): static
    {
        if (filter_var($local_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->domain = AF_INET;

        } elseif (filter_var($local_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->domain = AF_INET6;

        } else {
            $this->domain = AF_UNIX;
        }

        $this->local_address = $local_address;
        return $this;
    }


    /**
     * Returns the port property of this PhoSocketServer
     *
     * @return int
     */
    public function getLocalPort(): int
    {
        return $this->local_port;
    }


    /**
     * Sets the port property of this PhoSocketServer
     *
     * @param $local_port
     *
     * @return static
     */
    public function setLocalPort($local_port): static
    {
        $this->local_port = $local_port;
        return $this;
    }


    /**
     * Returns the timeout property of this PhoSocketServer
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }


    /**
     * Sets the timeout property of this PhoSocketServer
     *
     * @param int|null $timeout
     *
     * @return static
     */
    public function setTimeout(?int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }


    /**
     * Returns the domain property of this PhoSocketServer
     *
     * @return int
     */
    public function getDomain(): int
    {
        return $this->domain;
    }


    /**
     * Sets the domain property of this PhoSocketServer
     *
     * @param $domain
     *
     * @return static
     */
    public function setDomain($domain): static
    {
        $this->domain = $domain;
        return $this;
    }


    /**
     * Returns the master_socket property of this PhoSocketServer
     *
     * @return PhoSocket|null
     */
    public function getMasterSocket(): ?PhoSocket
    {
        return $this->master_socket;
    }


    /**
     * Sets the master_socket property of this PhoSocketServer
     *
     * @param PhoSocket|null $master_socket
     *
     * @return static
     */
    public function setMasterSocket(?PhoSocket $master_socket): static
    {
        $this->master_socket = $master_socket;
        return $this;
    }
}
