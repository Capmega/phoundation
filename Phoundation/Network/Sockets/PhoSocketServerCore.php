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
use Phoundation\Data\Traits\TraitDataUSleep;
use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\Exception\SocketServerException;
use Phoundation\Network\Sockets\Interfaces\PhoSocketServerInterface;
use Phoundation\Security\Incidents\Incident;
use Throwable;


class PhoSocketServerCore implements PhoSocketServerInterface
{
    use TraitDataUSleep;


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
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see run} you do not need to call this method.
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
        $this->shutDownEverything();
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
     * Run the Server for as long as server iteration returns true.
     *
     * @param bool $exception
     *
     * @throws SocketException
     */
    public function run(bool $exception = false): void
    {
        try{
            $this->ensureMasterSocketStarted();

            while ($this->processServerIteration($exception)) {
                usleep($this->usleep);
            }

            $this->shutDownEverything();

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
                if ($this->triggerHooks(static::HOOK_TIMEOUT, $this->master_socket) === false) {
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
     * Accepts a new connection in the Master Socket
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

        if ($this->triggerHooks(static::HOOK_CONNECT, $socket) === false) {
            usleep($this->usleep);
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
            $this->disconnect($client);
            return true;
        }

        return $this->triggerHooks(static::HOOK_INPUT, $client, $input);
    }


    /**
     * Read Functionality.
     *
     * @param PhoSocket $client
     *
     * @return string
     */
    protected function read(PhoSocket $client): string
    {
        try {
            $return = $client->read($this->max_read, $this->read_type);

        } catch (Throwable $e) {
            throw SocketServerException::new($e->getMessage(), $e);
        }

        return $return;
    }


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void Whether or not to continue running the server (true: continue, false: shutdown)
     */
    public function disconnect(PhoSocket $client, string $message = ''): void
    {
        $clientIndex = array_search($client, $this->clients, true);

        if ($clientIndex === false) {
            return;
        }

        $this->triggerHooks(static::HOOK_DISCONNECT, $this->clients[$clientIndex], $message);
        $this->clients[$clientIndex]->close();

        unset($this->clients[$clientIndex], $client);
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

                if ($continue === static::RETURN_HALT_HOOK) {
                    break;
                }

                if ($continue === static::RETURN_HALT_SERVER) {
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
    protected function shutDownEverything(): void
    {
        foreach ($this->clients as $client) {
            $this->disconnect($client);
        }

        try {
            $this->master_socket?->close();

        } catch (Throwable $e) {
            //Ignore 'harmless' error
            if (!str_contains($e->getMessage(), 'must not be accessed before initialization')) {
                throw SocketServerException::new($e->getMessage());
            }
        }

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
     * Returns the hooks property of this PhoSocketServer
     * 
     * @return callable[][]
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }


    /**
     * Sets the hooks property of this PhoSocketServer
     *
     * @param $hooks
     *
     * @return static
     */
    public function setHooks($hooks): static
    {
        $this->hooks = $hooks;
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