<?php

namespace Phoundation\Network\Sockets\Interfaces;

use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\PhoSocket;
use Phoundation\Network\Sockets\PhoSocketServerCore;

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
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void Whether or not to continue running the server (true: continue, false: shutdown)
     */
    public function disconnect(PhoSocket $client, string $message = ''): void;


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