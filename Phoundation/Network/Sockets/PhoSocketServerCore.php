<?php

/**
 * Class PhoSocketServerCore
 *
 * Core functionality for PhoSocketServer
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataStringName;
use Phoundation\Data\Traits\TraitDataUSleep;
use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Network\Sockets\Interfaces\PhoSocketInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Network\Sockets\Exception\SocketAddressInUseException;
use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\Exception\SocketServerException;
use Phoundation\Network\Sockets\Interfaces\PhoSocketServerInterface;
use Throwable;

class PhoSocketServerCore implements PhoSocketServerInterface
{
    use TraitDataUSleep;
    use TraitDataStringName;


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
     * @var string|null
     */
    protected ?string $listen_address = null;

    /**
     * Port Number.
     *
     * @var int|null
     */
    protected ?int $listen_port = null;

    /**
     * Seconds to wait on a socket before timing out.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Domain
     *
     * @see http://php.net/manual/en/function.socket-create.php
     *
     * @var EnumNetworkSocketDomain One of AF_INET, AF_INET6, AF_UNIX
     */
    protected EnumNetworkSocketDomain $domain = EnumNetworkSocketDomain::AF_INET;

    /**
     * The Master Socket.
     *
     * @var PhoSocketInterface|null
     */
    protected ?PhoSocketInterface $master_socket = null;

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
     * @var array $clients
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
     * Tracks the number of messages sent within the last second
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
     * Tracks the number of seconds that this socket server has been listening
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
     * Log statistics every $log_statistics messages
     *
     * @var int $log_statistics
     */
    protected int $log_statistics = 100;


    /**
     * PhoSocketServerCore class destructor
     */
    public function __destruct()
    {
        $this->disconnectEverything();
    }


    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see listen} you do not need to call this method.
     *
     * @throws SocketException
     */
    protected function listen(): static
    {
        Core::setTimeout(0);

        $this->master_socket = PhoSocket::create($this->domain, SOCK_STREAM, 0)
                                        ->bind($this->listen_address, $this->listen_port)
                                        ->listen();

        Log::success(ts('Socket server is now listening on ":address::port"', [
            ':address' => $this->listen_address,
            ':port'    => $this->listen_port,
        ]));

        return $this;
    }


    /**
     * Checks if the master socket is started,
     *
     * @return static
     */
    protected function ensureMasterSocketStarted(): static
    {
        if ($this->master_socket === null) {
            $this->listen();
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
            $this->second_count   = 0;
            $this->time           = $time;
        }
    }


    /**
     * Returns the number of seconds that this socket server has been connected
     *
     * @return int
     */
    public function getConnectedSeconds(): int
    {
        return $this->total_seconds;
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
        return $this->second_count;
    }


    /**
     * Returns the average number of messages received per second based on the total time elapsed and the
     * total message count
     *
     * @return float
     */
    public function getMessagesPerSecond(): float
    {
        return $this->second_count / $this->total_seconds;
    }


    /**
     * Returns the current amount of open connections
     *
     * @return int
     */
    public function getOpenConnections(): int
    {
        return count($this->clients);
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
            if ($e instanceof SocketAddressInUseException) {
                throw $e;
            }

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

        } catch (Throwable $e) {
            if ($exception) {
                throw SocketServerException::new($e->getMessage(), $e);
            }

            Incident::new()
                    ->setException($e)
                    ->setSeverity(EnumSeverity::severe)
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
     * Logs statistical information
     *
     * @return static
     */
    protected function logStatistics(): static
    {
        if (!fmod($this->total_count, $this->log_statistics)) {
            Log::debug('Statistical information:', echo_header: false);
            Log::printr([
                'total amount of message' => $this->total_count,
                'total connection time'   => $this->total_seconds,
                'messages per second'     => $this->second_average,
            ], echo_header: false);
        }

        return $this;
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

        return $this->logStatistics()
                    ->triggerInputHooks($client, $input);
    }


    /**
     * Read functionality.
     *
     * @param PhoSocketInterface $client
     *
     * @return string
     */
    protected function read(PhoSocketInterface $client): string
    {
        return $client->read($this->max_read, $this->read_type);
    }


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocketInterface $client
     * @param string             $message Disconnection Message. Can be used to trigger a disconnect with a status code
     *
     * @return void
     */
    public function disconnect(PhoSocketInterface $client, string $message = ''): void
    {
        Log::action(ts('Disconnecting client ":address::port" from service ":name"', [
            ':address' => $client->getRemoteAddress(),
            ':port'    => $client->getRemotePort(),
            ':name'    => $this->getName(),
        ]));

        $client_index = array_search($client, $this->clients, true);

        if ($client_index === false) {
            return;
        }

        $this->triggerDisconnectionHooks($this->clients[$client_index]);
        $this->clients[$client_index]->disconnect();

        unset($this->clients[$client_index], $client);
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket sends a message to this PhoSocketServer
     *
     * @param PhoSocketInterface $client
     * @param string|null        $input Message Sent along with the Trigger
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerInputHooks(PhoSocketInterface $client, ?string $input = null): bool
    {
        try {
            $this->total_count++;

            foreach ($this->input_hooks as $callable) {
                $callable($this, $client, $input);
            }

        } catch (Throwable $e) {
            Incident::new()
                    ->setException($e)
                    ->setTitle(tr('Socket server input hook failed'))
                    ->setNotifyRoles('developer')
                    ->setDetails([
                        'data' => $input
                    ])
                    ->save();
        }

        return true;
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket connects to this PhoSocketServer
     *
     * @param PhoSocketInterface $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerConnectionHooks(PhoSocketInterface $client): bool
    {
        try {
            Log::success(ts('Service ":name" received connection on local address ":local_ip::local_port" from remote address ":remote_ip::remote_port"', [
                ':name'        => $this->getName(),
                ':local_ip'    => $client->getLocalAddress(),
                ':local_port'  => $client->getLocalPort(),
                ':remote_ip'   => $client->getRemoteAddress(),
                ':remote_port' => $client->getRemotePort(),
            ]));

            foreach ($this->connection_hooks as $callable) {
                $callable($this, $client);
            }

            Log::notice(ts('Service ":name" has ":count" open connections', [
                ':name'  => $this->getName(),
                ':count' => $this->getOpenConnections(),
            ]));

        } catch (Throwable $e) {
            Incident::new()
                    ->setException($e)
                    ->setTitle(tr('Socket server connection hook failed'))
                    ->setNotifyRoles('developer')
                    ->save();
        }

        return true;
    }


    /**
     * Triggers the hooks in the input_hooks array. Called when client socket disconnects from this PhoSocketServer
     *
     * @param PhoSocketInterface $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerDisconnectionHooks(PhoSocketInterface $client): bool
    {
        try {
            Log::action(ts('Client ":address::port" disconnected from service ":name"', [
                ':address' => $client->getRemoteAddress(),
                ':port'    => $client->getRemotePort(),
                ':name'    => $this->getName(),
            ]));

            foreach ($this->disconnection_hooks as $callable) {
                $callable($this, $client);
            }

            Log::notice(ts('Service ":name" has ":count" open connections', [
                ':name'  => $this->getName(),
                ':count' => $this->getOpenConnections(),
            ]));

        } catch (Throwable $e) {
            Incident::new()
                    ->setException($e)
                    ->setTitle(tr('Socket server disconnection hook failed'))
                    ->setNotifyRoles('developer')
                    ->save();
        }

        return true;
    }


    /**
     * Triggers the hooks in the timeout_hooks array.
     *
     * @param PhoSocketInterface $client
     *
     * @return bool Whether to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerTimeoutHooks(PhoSocketInterface $client): bool
    {
        try {
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

        } catch (Throwable $e) {
            Incident::new()
                    ->setException($e)
                    ->setTitle(tr('Socket server timeout hook failed'))
                    ->setNotifyRoles('developer')
                    ->save();
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
     * Attach a Callback function that will be called when a message is sent from the PhoSocketInterface client
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
     * @param int|string $key The callable to be removed
     *
     * @return static
     */
    public function removeConnectionHook(int|string $key): static
    {
        unset($this->connection_hooks[$key]);
        return $this;
    }


    /**
     * Remove the provided Disconnection Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeDisconnectionHook(int|string $key): static
    {
        unset($this->disconnection_hooks[$key]);
        return $this;
    }


    /**
     * Remove the provided Input Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeInputHook(int|string $key): static
    {
        unset($this->input_hooks[$key]);
        return $this;
    }


    /**
     * Remove the provided Timeout Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeTimeoutHook(int|string $key): static
    {
        unset($this->timeout_hooks[$key]);
        return $this;
    }


    /**
     * Clears all hook functions for this server
     *
     * @return static
     */
    public function clearHooks(): static
    {
        $this->input_hooks         = [];
        $this->timeout_hooks       = [];
        $this->connection_hooks    = [];
        $this->disconnection_hooks = [];

        return $this;
    }


    /**
     * Disconnect all the Clients and shut down the server.
     *
     * @return void
     */
    protected function disconnectEverything(): void
    {
        Log::action(ts('Disconnecting all sockets'), 8);

        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }

        $this->closeMasterSocket();

        Log::success(ts('Disconnected all sockets'), 4);

        unset(
            $this->hooks,
            $this->listen_address,
            $this->listen_port,
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
     * @return static
     */
    protected function closeMasterSocket(): static
    {
        try {
            if (isset($this->master_socket)) {
                $this->master_socket->shutdown();
                $this->master_socket->disconnect(0, true);
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
     * @return string|null
     */
    public function getListenAddress(): ?string
    {
        return $this->listen_address;
    }


    /**
     * Sets the address property of this PhoSocketServer
     *
     * @param string|null $listen_address
     *
     * @return static
     */
    public function setListenAddress(?string $listen_address): static
    {
        if ($listen_address === null) {
            if (filter_var($listen_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->domain = EnumNetworkSocketDomain::AF_INET;

            } elseif (filter_var($listen_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $this->domain = EnumNetworkSocketDomain::AF_INET6;

            } else {
                $this->domain = EnumNetworkSocketDomain::AF_UNIX;
            }
        }

        $this->listen_address = $listen_address;
        return $this;
    }


    /**
     * Returns the port property of this PhoSocketServer
     *
     * @return int
     */
    public function getListenPort(): int
    {
        return $this->listen_port;
    }


    /**
     * Sets the port property of this PhoSocketServer
     *
     * @param int|null $listen_port
     *
     * @return static
     */
    public function setListenPort(?int $listen_port): static
    {
        $this->listen_port = $listen_port;
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
     * @return EnumNetworkSocketDomain
     */
    public function getDomain(): EnumNetworkSocketDomain
    {
        return $this->domain;
    }


    /**
     * Sets the domain property of this PhoSocketServer
     *
     * @param EnumNetworkSocketDomain $domain
     *
     * @return static
     */
    public function setDomain(EnumNetworkSocketDomain$domain): static
    {
        $this->domain = $domain;
        return $this;
    }


    /**
     * Returns the master_socket property of this PhoSocketServer
     *
     * @return PhoSocketInterface|null
     */
    public function getMasterSocket(): ?PhoSocketInterface
    {
        return $this->master_socket;
    }


    /**
     * Sets the master_socket property of this PhoSocketServer
     *
     * @param PhoSocketInterface|null $master_socket
     *
     * @return static
     */
    public function setMasterSocket(?PhoSocketInterface $master_socket): static
    {
        $this->master_socket = $master_socket;
        return $this;
    }
}
