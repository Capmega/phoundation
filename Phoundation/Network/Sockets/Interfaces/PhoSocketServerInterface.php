<?php

namespace Phoundation\Network\Sockets\Interfaces;

use Phoundation\Core\Core;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Network\Sockets\Exception\SocketException;
use Phoundation\Network\Sockets\Exception\SocketServerException;
use Phoundation\Network\Sockets\PhoSocket;
use Phoundation\Network\Sockets\PhoSocketServerCore;
use Phoundation\Security\Incidents\Incident;

interface PhoSocketServerInterface
{
    /**
     * Start the server, binding to ports and listening for connections.
     *
     * If you call {@see execute} you do not need to call this method.
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

    public function getOnlineSeconds(): int;


    public function getTotalMessages(): int;


    public function getMessagesThisSecond(): int;


    public function getMessagesPerSecond(): float;


    /**
     * Run the Server for as long as server iteration returns true.
     *
     * @param bool $exception
     *
     * @throws SocketException
     */
    public function execute(bool $exception = false): void;


    /**
     * Disconnect the supplied Client Socket.
     *
     * @param PhoSocket $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void
     */
    public function disconnect(PhoSocket $client, string $message = ''): void;


    /**
     * Attach a Callback function that will be called when a connection is initiated
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addConnectionHook(callable $callable): static;


    /**
     * Attach a Callback function that will be called when a connection is terminated
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addDisconnectionHook(callable $callable): static;


    /**
     * Attach a Callback function that will be called when a message is sent from the PhoSocket client
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addInputHook(callable $callable): static;


    /**
     * Attach a Callback function that will be called when a connection is timed out
     *
     * @param callable $callable A callable with the signature (Server, Socket, string). Callable should return false
     *                           if it wishes to stop the server, and true if it wishes to continue.
     *
     * @return static
     */
    public function addTimeoutHook(callable $callable): static;


    /**
     * Remove the provided Connection Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeConnectionHook(string $command, callable $callable): static;


    /**
     * Remove the provided Disconnection Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeDisconnectionHook(string $command, callable $callable): static;

    /**
     * Remove the provided Input Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeInputHook(string $command, callable $callable): static;


    /**
     * Remove the provided Timeout Callable from the provided Hook.
     *
     * @param string   $command  Hook to remove callable from
     * @param callable $callable The callable to be removed
     *
     * @return static
     */
    public function removeTimeoutHook(string $command, callable $callable): static;


    /**
     * Returns the address property of this PhoSocketServer
     *
     * @return string
     */
    public function getLocalAddress(): string;

    /**
     * Sets the address property of this PhoSocketServer
     *
     * @param $local_address
     *
     * @return static
     */
    public function setLocalAddress($local_address): static;

    /**
     * Returns the port property of this PhoSocketServer
     *
     * @return int
     */
    public function getLocalPort(): int;

    /**
     * Sets the port property of this PhoSocketServer
     *
     * @param $local_port
     *
     * @return static
     */
    public function setLocalPort($local_port): static;


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