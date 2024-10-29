<?php

/**
 * Interface PhoSocketServerInterface
 *
 * Interface for PhoSocket Server
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Network\Sockets\Exception\SocketException;


interface PhoSocketServerInterface
{
    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see run} you do not need to call this method.
     *
     * @throws SocketException
     */
    public function start(): static;


    /**
     * Called when object destroyed
     */
    public function __destruct();


    /**
     * Checks if the master socket is started,
     *
     * @return $this
     */
    public function ensureMasterSocketStarted(): static;



    /**
     * Run the Server for as long as server iteration returns true.
     *
     * @throws SocketException
     */
    public function run(): void;


    /**
     * Processes server operations for one iteration of the main loop, including handling new connections and
     * client inputs.
     *
     * @return bool Returns false if the server should shut down, true otherwise.
     *
     * @throws SocketException
     */
    protected function processServerIteration(): bool;


    /**
     * Check if the master socket is started, throw exception if now
     *
     * @return void
     */
    protected function validateSocket(): void;


    /**
     * Accepts a new connection in the Master Socket
     *
     * @param array $read
     *
     * @return void
     */
    protected function acceptNewConnection(array &$read): void;


    /**
     * Handle Client Input
     *
     * @param $client
     *
     * @return bool
     */
    protected function handleClientInput($client): bool;


    /**
     * Read Functionality.
     *
     * @param PhoSocket $client
     *
     * @return string
     */
    protected function read(PhoSocket $client): string;


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void Whether or not to continue running the server (true: continue, false: shutdown)
     */
    public function disconnect(PhoSocket $client, string $message = ''): void;


    /**
     * Triggers the hooks for the supplied command.
     *
     * @param string      $command Hook to listen for (e.g. HOOK_CONNECT, HOOK_INPUT, HOOK_DISCONNECT, HOOK_TIMEOUT)
     * @param PhoSocket   $client
     * @param string|null $input   Message Sent along with the Trigger
     *
     * @return bool Whether or not to continue running the server (true: continue, false: shutdown)
     */
    protected function triggerHooks(string $command, PhoSocket $client, ?string $input = null): bool;

    /**
     * Attach a Listener to a Hook.
     *
     * @param string   $command  Hook to listen for
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addHook(string $command, callable $callable): static;


    /**
     * Remove the provided Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeHook(string $command, callable $callable): static;


    /**
     * Disconnect all the Clients and shut down the server.
     *
     * @return void
     */
    private function shutDownEverything(): void;


    /**
     * Returns the hooks property of this PhoSocketServer
     * 
     * @return callable[][]
     */
    public function getHooks(): array;

    /**
     * Sets the hooks property of this PhoSocketServer
     *
     * @param $hooks
     *
     * @return static
     */
    public function setHooks($hooks): static;


    /**
     * Returns the address property of this PhoSocketServer
     *
     * @return string
     */
    public function getAddress(): string;


    /**
     * Sets the address property of this PhoSocketServer
     *
     * @param $address
     *
     * @return static
     */
    public function setAddress($address): static;

    
    /**
     * Returns the port property of this PhoSocketServer
     *
     * @return int
     */
    public function getPort(): int;


    /**
     * Sets the port property of this PhoSocketServer
     *
     * @param $port
     *
     * @return static
     */
    public function setPort($port): static;

    
    /**
     * Returns the timeout property of this PhoSocketServer
     *
     * @return int|null
     */
    public function getTimeout(): ?int;

    /**
     * Sets the timeout property of this PhoSocketServer
     *
     * @param int|null $timeout
     *
     * @return static
     */
    public function setTimeout(?int $timeout): static;


    /**
     * Returns the domain property of this PhoSocketServer
     *
     * @return int
     */
    public function getDomain(): int;


    /**
     * Sets the domain property of this PhoSocketServer
     *
     * @param $domain
     *
     * @return static
     */
    public function setDomain($domain): static;


    /**
     * Returns the master_socket property of this PhoSocketServer
     *
     * @return PhoSocket|null
     */
    public function getMasterSocket(): ?PhoSocket;


    /**
     * Sets the master_socket property of this PhoSocketServer
     *
     * @param PhoSocket|null $master_socket
     *
     * @return static
     */
    public function setMasterSocket(?PhoSocket $master_socket): static;
}