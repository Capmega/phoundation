<?php

namespace Phoundation\Network\Sockets\Interfaces;

use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Network\Sockets\Interfaces\PhoSocketInterface;
use Phoundation\Network\Sockets\Exception\SocketException;

interface PhoSocketServerInterface
{
    /**
     * Called when object destroyed
     */
    public function __destruct();


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
     * @param PhoSocketInterface $client
     * @param string    $message Disconnection Message.  Could be used to trigger a disconnect with a status code
     *
     * @return void
     */
    public function disconnect(PhoSocketInterface $client, string $message = ''): void;


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
     * @param int|string $key
     *
     * @return static
     */
    public function removeConnectionHook(int|string $key): static;


    /**
     * Remove the provided Disconnection Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeDisconnectionHook(int|string $key): static;


    /**
     * Remove the provided Input Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeInputHook(int|string $key): static;


    /**
     * Remove the provided Timeout Callable from the provided Hook.
     *
     * @param int|string $key
     *
     * @return static
     */
    public function removeTimeoutHook(int|string $key): static;


    /**
     * Returns the address property of this PhoSocketServer
     *
     * @return string|null
     */
    public function getListenAddress(): ?string;

    /**
     * Sets the address property of this PhoSocketServer
     *
     * @param string|null $listen_address
     *
     * @return static
     */
    public function setListenAddress(?string $listen_address): static;

    /**
     * Returns the port property of this PhoSocketServer
     *
     * @return int
     */
    public function getListenPort(): int;

    /**
     * Sets the port property of this PhoSocketServer
     *
     * @param int|null $listen_port
     *
     * @return static
     */
    public function setListenPort(?int $listen_port): static;


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
     * @return EnumNetworkSocketDomain
     */
    public function getDomain(): EnumNetworkSocketDomain;

    /**
     * Sets the domain property of this PhoSocketServer
     *
     * @param EnumNetworkSocketDomain $domain
     *
     * @return static
     */
    public function setDomain(EnumNetworkSocketDomain $domain): static;

    /**
     * Returns the master_socket property of this PhoSocketServer
     *
     * @return PhoSocketInterface|null
     */
    public function getMasterSocket(): ?PhoSocketInterface;

    /**
     * Sets the master_socket property of this PhoSocketServer
     *
     * @param PhoSocketInterface|null $master_socket
     *
     * @return static
     */
    public function setMasterSocket(?PhoSocketInterface $master_socket): static;
}
