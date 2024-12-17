<?php

/**
 * Class PhoSocket
 *
 * A wrapper around PHP \Socket that allows for more functionality.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @author    Harrison Macey <harrison@medinet.ca>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataStaticSourceArray;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Network\Interfaces\PhoSocketsInterface\PhoSocketInterface;
use Phoundation\Network\Sockets\Exception\SocketAddressInUseException;
use Phoundation\Network\Sockets\Exception\SocketConnectionRefusedException;
use Phoundation\Network\Sockets\Exception\SocketDisconnectedException;
use Phoundation\Network\Sockets\Exception\SocketException;
use Socket as SocketResource;
use Stringable;
use Throwable;


class PhoSocket implements Stringable, PhoSocketInterface
{
    use TraitDataStaticSourceArray;


    /**
     * Indicate whether this PhoSocket is open or closed
     *
     * @var bool $connected
     */
    protected bool $connected = false;

    /**
     * Should be set to one of the php predefined constants for Sockets - AF_UNIX, AF_INET, or AF_INET6
     *
     * @var EnumNetworkSocketDomain $domain
     */
    protected EnumNetworkSocketDomain $domain = EnumNetworkSocketDomain::AF_INET;

    /**
     * Should be set to one of the php predefined constants for Sockets - SOCK_STREAM, SOCK_DGRAM, SOCK_SEQPACKET,
     * SOCK_RAW, SOCK_RDM
     *
     * @var int $type
     */
    protected int $type;

    /**
     * Should be set to the protocol number to be used. Can use getprotobyname to get the value. Alternatively, there
     * are two predefined constants for Sockets that could be used - SOL_TCP, SOL_UDP
     *
     * @var int $protocol
     */
    protected int $protocol;

    /**
     * The IP address in string form for this socket. If the socket is not created or connected yet, value will be not
     * be set
     *
     * @var string|null $local_address
     */
    protected ?string $local_address = null;

    /**
     * The Port value in int form for this socket. If the socket is not created or connected yet, value will be not be
     * set
     *
     * @var int|null $local_port
     */
    protected ?int $local_port = null;

    /**
     * The remote IP address for this socket
     *
     * @var string|null $remote_address
     */
    protected ?string $remote_address = null;

    /**
     * The remote port for this socket
     *
     * @var int|null $remote_port
     */
    protected ?int $remote_port = null;

    /**
     * The PHP Socket resource that this PHOSocket object uses.
     *
     * @var SocketResource $resource
     */
    protected SocketResource $resource;

    /**
     * Array that stores the PHP \Socket Options
     *
     * @var array $options
     */
    protected array $options;


    /**
     * Sets up the Socket Resource and stores it in the local map.
     *
     * Please use the <code>create</code> method to create new instances of this class.
     *
     * @param SocketResource $resource The php socket resource. This is just a reference to the socket object created
     *                                 using the <code>socket_create</code> method.
     *
     * @see PhoSocket::create()
     *
     */
    protected function __construct(SocketResource $resource, bool $connected)
    {
        $this->options   = [];
        $this->resource  = $resource;
        $this->connected = $connected;

        $this->initializeRemoteName($resource)
             ->initializeLocalName($resource);

        static::$source[$this->__toString()] = $this;
    }


    /**
     * Cleans up the Socket and dereferences the internal resource.
     */
    public function __destruct()
    {
        $this->disconnect(0, true);
        unset($this->resource);
    }


    /**
     * Return the php socket resource name.
     *
     * <p>Resources are always converted to strings with the structure "Resource id#1", where 1 is the resource number
     * assigned to the resource by PHP at runtime. While the exact structure of this string should not be relied on and
     * is subject to change, it will always be unique for a given resource within the lifetime of the script execution
     * and won't be reused.</p>
     *
     * <p>If the resource object has been dereferrenced (set to <code>null</code>), this will return an empty
     * string.</p>
     *
     * @return string The string representation of the resource or an empty string if the resource was null.
     */
    public function __toString(): string
    {
        if (isset($this->resource)) {
            return spl_object_hash($this->resource);
        }

        return '';
    }


    /**
     * Returns a new static object
     *
     * @param SocketResource $resource
     * @param bool           $connected
     *
     * @return static
     */
    protected static function new(SocketResource $resource, bool $connected): static
    {
        return new static($resource, $connected);
    }


    /**
     * Accept a connection.
     *
     * <p>After the socket has been created using <code>create()</code>, bound to a name with
     * <code>bind()</code>, and told to listen for connections with <code>listen()</code>, this function will accept
     * incoming connections on that socket. Once a successful connection is made, a new Socket resource is returned,
     * which may be used for communication. If there are multiple connections queued on the socket, the first will be
     * used. If there are no pending connections, this will block until a connection becomes present. If socket has
     * been made non-blocking using <code>setBlocking()</code>, a <code>SocketException</code> will be thrown.</p>
     *
     * <p>The Socket returned by this method may not be used to accept new connections. The original listening Socket,
     * however, remains open and may be reused.</p>
     *
     * @return static A new Socket representation of the accepted socket.
     *
     * @throws SocketException If the Socket is set as non-blocking and there are no pending connections.
     *
     * @see PhoSocket::bind()
     * @see PhoSocket::listen()
     * @see PhoSocket::setBlocking()
     * @see PhoSocket::create()
     */
    public function accept(): static
    {
        $resource = socket_accept($this->resource);

        if (!$resource) {
            throw SocketException::new(tr('Failed to accept socket'))
                                 ->setCode(socket_last_error($this->resource))
                                 ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return new static($resource, true);
    }


    /**
     * Binds a name to a socket.
     *
     * <p>Binds the name given in address to the php socket resource currently in use. This has to be done before a
     * connection is established using <code>connect()</code> or <code>listen()</code>.</p>
     *
     * @param string $address <p>If the socket is of the AF_INET family, the address is an IP in dotted-quad notation
     *                        (e.g. <code>127.0.0.1</code>).</p> <p>If the socket is of the AF_UNIX family, the address
     *                        is the path of the Unix-domain socket (e.g. <code>/tmp/my.sock</code>).</p>
     * @param int    $port    <p>(Optional) The port parameter is only used when binding an AF_INET socket, and
     *                        designates the port on which to listen for connections.</p>
     *
     * @return static Returns $this
     */
    public function bind(string $address, int $port = 0): static
    {
        try {
            $result = socket_bind($this->resource, $address, $port);

            if (!$result) {
                throw SocketException::new(tr('Failed to bind socket ":address::port"', [
                    ':address' => $address,
                    ':port'    => $port
                ]))
                ->setCode(socket_last_error($this->resource))
                ->addMessages(socket_strerror(socket_last_error($this->resource)));
            }

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Address already in use')) {
                throw new SocketAddressInUseException(tr('Cannot bind to address ":address::port", it is already in use', [
                    ':address' => $address,
                    ':port'    => $port
                ]));
            }
        }

        return $this->setLocalAddress($address)->setLocalPort($port);
    }


    /**
     * Wrapper for PhoSocket::getOpen()
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->getConnected();
    }


    /**
     * Will throw SocketException if the socket is open
     *
     * @param string $action
     *
     * @return $this
     */
    protected function ensureClosed(string $action): static
    {
        if ($this->isConnected()) {
            throw new SocketException(tr('Cannot perform action ":action", socket is open', [
                ':action' => $action
            ]));
        }

        return $this;
    }


    /**
     * Disconnect a PhoSocket and unsets it from the source array
     *
     * @param int  $linger
     * @param bool $force
     *
     * @return PhoSocket
     */
    public function disconnect(int $linger = 0, bool $force = false): static
    {
        if ($this->getConnected()) {
            $hash = $this->__toString();

            unset(static::$source[$hash]);

            try {
                // TODO This must be taken from class variables that default to configurable settings
                $linger_options = [
                    'l_linger' => 1,
                    'l_onoff'  => 1
                ];

                socket_set_block($this->resource);
                socket_set_option($this->resource, SOL_SOCKET, SO_LINGER, $linger_options);

                socket_close($this->resource);
                $this->connected = false;

            } catch (Throwable $e) {
                if (!str_contains($e->getMessage(), 'has already been closed')) {
                    throw SocketException::new(tr('Failed to close socket'))
                                         ->setCode(socket_last_error($this->resource))
                                         ->addMessages(socket_strerror(socket_last_error($this->resource)));
                }
            }
        }

        return $this;
    }


    /**
     * Connect to a socket.
     *
     * <p>Initiate a connection to the address given using the current php socket resource, which must be a valid socket
     * resource created with <code>create()</code>.
     *
     * @return static Returns <code>true</code> if connection was successful.
     *
     * @see PhoSocket::listen()
     * @see PhoSocket::create()
     * @see PhoSocket::bind()
     */
    public function connect(): static
    {
        Log::action(tr('Opening connection for socket ":id" to address ":address::port"', [
            ':id'      => $this->__toString(),
            ':address' => $this->remote_address,
            ':port'    => $this->remote_port,
        ]), 3);

        try {
            $result = socket_connect($this->resource, $this->remote_address, $this->remote_port);

            if (!$result) {
                throw SocketException::new(tr('Socket connect to address ":address::port" returned false', [
                    ':address' => $this->remote_address,
                    ':port'    => $this->remote_port
                ]))
                ->setCode(socket_last_error($this->resource))
                ->addMessages(socket_strerror(socket_last_error($this->resource)));
            }

            $this->connected = true;

            $this->initializeLocalName($this->resource)
                 ->initializeRemoteName($this->resource);

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Connection refused')) {
                throw new SocketConnectionRefusedException(tr('Connection to address ":address::port" refused', [
                    ':address' => $this->remote_address,
                    ':port'    => $this->remote_port
                ]), $e);
            }

            throw new SocketException(tr('Failed to connect to address ":address::port"', [
                ':address' => $this->remote_address,
                ':port'    => $this->remote_port
            ]), $e);
        }

        return $this;
    }


    /**
     * Returns the Domain property of this PhoSocket
     *
     * @return EnumNetworkSocketDomain
     */
    public function getDomain(): EnumNetworkSocketDomain
    {
        return $this->domain;
    }


    /**
     * Sets the Domain property of this PhoSocket
     *
     * @param EnumNetworkSocketDomain $domain
     *
     * @return static
     */
    public function setDomain(EnumNetworkSocketDomain $domain): static
    {
        $this->domain = $domain;
        return $this;
    }


    /**
     * Returns the 'open' boolean property of this PhoSocket
     *
     * @return bool
     */
    public function getConnected(): bool
    {
        return $this->connected;
    }


    /**
     * Returns the Type property of this PhoSocket
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }


    /**
     * Sets the Type property of this PhoSocket
     *
     * @param int $type
     *
     * @return static
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }


    /**
     * Returns the Protocol property of this PhoSocket
     *
     * @return int
     */
    public function getProtocol(): int
    {
        return $this->protocol;
    }


    /**
     * Sets the Protocol property of this PhoSocket
     *
     * @param int $protocol
     *
     * @return static
     */
    public function setProtocol(int $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }


    /**
     * Returns the Resource property of this PhoSocket
     *
     * @return SocketResource
     */
    public function getResource(): SocketResource
    {
        return $this->resource;
    }


    /**
     * Sets the Resource property of this PhoSocket
     *
     * @param SocketResource $resource
     *
     * @return static
     */
    public function setResource(SocketResource $resource): static
    {
        $this->resource = $resource;
        return $this;
    }


    /**
     * Returns the Port value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return int|null
     */
    public function getLocalPort(): ?int
    {
        return $this->local_port;
    }


    /**
     * Sets the Port value property for the PhoSocket, then returns the PhoSocket
     *
     * @param int|null $local_port The port number
     *
     * @return static
     */
    protected function setLocalPort(?int $local_port): static
    {
        $this->local_port = $local_port;
        return $this;
    }


    /**
     * Returns the Address value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return string|null
     */
    public function getLocalAddress(): ?string
    {
        return $this->local_address;
    }


    /**
     * Sets the Address value property for the PhoSocket, then returns the PhoSocket
     *
     * @param string|null $local_address The address number
     *
     * @return static
     */
    protected function setLocalAddress(?string $local_address): static
    {
        $this->local_address = $local_address;
        return $this;
    }


    /**
     * Returns the Port value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return int|null
     */
    public function getRemotePort(): ?int
    {
        return $this->remote_port;
    }


    /**
     * Sets the Port value property for the PhoSocket, then returns the PhoSocket
     *
     * @param int|null $remote_port The port number
     *
     * @return static
     */
    public function setRemotePort(?int $remote_port): static
    {
        $this->ensureClosed(tr('set remote port'))
             ->remote_port = $remote_port;


        return $this;
    }


    /**
     * Returns the Address value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return string|null
     */
    public function getRemoteAddress(): ?string
    {
        return $this->remote_address;
    }


    /**
     * Sets the Address value property for the PhoSocket, then returns the PhoSocket
     *
     * @param string|null $remote_address The address number
     *
     * @return static
     */
    public function setRemoteAddress(?string $remote_address): static
    {
        $this->ensureClosed(tr('set remote address'))
             ->remote_address = $remote_address;

        return $this;
    }


    /**
     * Build Socket objects based on an array of php socket resources.
     *
     * @param SocketResource[] $resources A list of php socket resource objects.
     *
     * @return PhoSocket[] <p>Returns an array of Socket objects built from the given php socket resources.</p>
     */
    protected static function constructFromResources(array $resources): array
    {
        $sockets = [];

        foreach ($resources as $resource) {
            $sockets[] = new static($resource, true);
        }

        return $sockets;
    }


    /**
     * Create a socket.
     *
     * <p>Creates and returns a Socket. A typical network connection is made up of two sockets, one performing the role
     * of the client, and another performing the role of the server.</p>
     *
     * @param EnumNetworkSocketDomain $domain <p>The domain parameter specifies the protocol family to be used by the
     *                                        socket.</p><p><code>AF_INET</code> - IPv4 Internet based protocols. TCP
     *                                        and UDP are common protocols of this protocol family. </p><p>
     *                                        <code>AF_INET6</code> - IPv6 Internet based protocols. TCP and UDP are
     *                                        common protocols of this protocol family.</p><p><code>AF_UNIX</code> -
     *                                        Local communication protocol family. High efficiency and low overhead make
     *                                        it a great form of IPC (Interprocess Communication).</p>
     * @param int $type     <p>The type parameter selects the type of communication to be used by the socket.</p><p>
     *                      <code>SOCK_STREAM</code> - Provides sequenced, reliable, full-duplex, connection-based byte
     *                      streams. An* out-of-band data transmission mechanism may be supported. The TCP protocol is
     *                      based on this socket type .</p><p> <code>SOCK_DGRAM</code> - Supports datagrams
     *                      (connectionless, unreliable messages of a fixed maximum length). The UDP protocol is based
     *                      on this socket type.</p><p><code>SOCK_SEQPACKET</code> - Provides a sequenced, reliable,
     *                      two-way connection-based data transmission path for datagrams of fixed maximum length; a
     * @param int $protocol <p>The protocol parameter sets the specific protocol within the specified domain to be used
     *                      when communicating on the returned socket. The proper value can be retrieved by name by
     *                      using <code>getprotobyname()</code>. If the desired protocol is TCP, or UDP the
     *                      corresponding constants <code>SOL_TCP</code>, and <code>SOL_UDP</code> can also be used.
     *                      <p><p>Some of the common protocol types</p><p> icmp - The Internet Control Message Protocol
     *                      is used primarily by gateways and hosts to report errors in datagram communication. The
     *                      "ping" command (present in most modern operating systems) is an example application of ICMP.
     *                      </p><p>udp - The User Datagram Protocol is a connectionless, unreliable, protocol with fixed
     *                      record lengths. Due to these aspects, UDP requires a minimum amount of protocol overhead.
     *                      </p><p>tcp - The Transmission Control Protocol is a reliable, connection based, stream
     *                      oriented, full duplex protocol. TCP guarantees that all data packets will be received in the
     *                      order in which they were sent. If any packet is somehow lost during communication, TCP will
     *                      automatically retransmit the packet until the destination host acknowledges that packet. For
     *                      reliability and performance reasons, the TCP implementation itself decides the appropriate
     *                      octet boundaries of the underlying datagram communication layer. Therefore, TCP applications
     *                      must allow for the possibility of partial record transmission.</p>
     *
     * @return static Returns a Socket object based on the successful creation of the php socket.
     *
     * @throws SocketException If there is an error creating the php socket.
     */
    public static function create(EnumNetworkSocketDomain $domain, int $type, int $protocol): static
    {
        $result = socket_create($domain->value, $type, $protocol);

        if (!$result) {
            throw SocketException::new(tr('Failed to create socket with domain ":domain", type ":type", protocol ":protocol"', [
                ':domain'   => $domain,
                ':type'     => $type,
                ':protocol' => $protocol
            ]));
        }

        return static::new($result, false)
                     ->setDomain($domain)
                     ->setType($type)
                     ->setProtocol($protocol);
    }


    /**
     * Opens a socket on port to accept connections.
     *
     * <p>Creates a new socket resource of type <code>AF_INET</code> listening on all local interfaces on the given
     * port waiting for new connections.</p>
     *
     * @param int $port The port on which to listen on all interfaces.
     * @param int $backlog <p>The backlog parameter defines the maximum length the queue of pending connections may
     *                     grow to. <code>SOMAXCONN</code> may be passed as the backlog parameter.</p>
     *
     * @return static Returns a Socket object based on the successful creation of the php socket.
     *
     * @throws SocketException If the socket is not successfully created.
     *
     * @see PhoSocket::bind()
     * @see PhoSocket::listen()
     * @see PhoSocket::create()
     */
    public static function createListen(int $port, EnumNetworkSocketDomain $domain = EnumNetworkSocketDomain::AF_INET, int $backlog = 128): static
    {
        $result = socket_create_listen($port, $backlog);

        if (!$result) {
            throw SocketException::new(tr('Failed to create socket and listen on port ":port"', [
                ':port' => $port
            ]));
        }

        return static::new($result, true)
                     ->setDomain($domain);
    }


    /**
     * Creates a pair of indistinguishable sockets and stores them in an array.
     *
     * <p>Creates two connected and indistinguishable sockets. This function is commonly used in IPC (InterProcess
     * Communication).</p>
     *
     * @param EnumNetworkSocketDomain $domain <p>The domain parameter specifies the protocol family to be used by the
     *                                        socket. See <code>create()</code> for the full list.</p>
     * @param int $type                       <p>The type parameter selects the type of communication to be used by the
     *                                        socket. See <code>create()</code> for the full list.</p>
     * @param int $protocol                   <p>The protocol parameter sets the specific protocol within the specified
     *                                        domain to be used when communicating on the returned socket. The proper
     *                                        value can be retrieved by name by using <code>getprotobyname()</code>. If
     *                                        the desired protocol is TCP, or UDP the corresponding constants
     *                                        <code>SOL_TCP</code>, and <code>SOL_UDP</code> can also be used. See
     *                                        <code>create()</code> for the full list of supported protocols.
     *
     * @return array                          An array of Socket objects containing identical sockets.
     *
     * @throws SocketException If the creation of the php sockets is not successful.
     * @see PhoSocket::create()
     */
    public static function createPair(EnumNetworkSocketDomain $domain, int $type, int $protocol): array
    {
        $array  = [];
        $result = socket_create_pair($domain->value, $type, $protocol, $array);

        if (!$result) {
            throw SocketException::new(tr('Failed to create socket pair at domain ":domain" with type ":type" and protocol ":protocol"', [
                ':domain'   => $domain,
                ':type'     => $type,
                ':protocol' => $protocol
            ]));
        }

        $sockets = static::constructFromResources($array);

        foreach ($sockets as $socket) {
            $socket->domain   = $domain;
            $socket->type     = $type;
            $socket->protocol = $protocol;
        }

        return $sockets;
    }


    /**
     * Gets socket options.
     *
     * <p>Retrieves the value for the option specified by the optname parameter for the current socket.</p>
     *
     * @param int $level   <p>The level parameter specifies the protocol level at which the option resides. For
     *                     example, to retrieve options at the socket level, a level parameter of
     *                     <code>SOL_SOCKET</code> would be used. Other levels, such as <code>TCP</code>, can be used by
     *                     specifying the protocol number of that level. Protocol
     *                     numbers can be found by using the <code>getprotobyname()</code> function.
     * @param int $optname <p><b>Available Socket Options</b></p><p><code>SO_DEBUG</code> - Reports whether debugging
     *                     information is being recorded. Returns int.</p><p><code>SO_BROADCAST</code> - Reports whether
     *                     transmission of broadcast messages is supported. Returns int.</p><p>
     *                     <code>SO_REUSERADDR</code> - Reports whether local addresses can be reused. Returns int.</p>
     *                     <p><code>SO_KEEPALIVE</code> - Reports whether connections are kept active with periodic
     *                     transmission of messages. If the connected socket fails to respond to these messages, the
     *                     connection is broken and processes writing to that socket are notified with a SIGPIPE signal.
     *                     Returns int.</p><p> <code>SO_LINGER</code> - Reports whether the socket lingers on
     *                     <code>close()</code> if data is present. By default, when the socket is closed, it attempts
     *                     to send all unsent data. In the case of a connection-oriented socket,
     *                     <code>close()</code> will wait for its peer to acknowledge the data.
     *                     If <code>l_onoff</code> is non-zero and <code>l_linger</code> is zero, all the unsent
     *                     data will be discarded and RST (reset) is sent to the peer in the case of a
     *                     connection-oriented socket. On the other hand, if <code>l_onoff</code> is non-zero and
     *                     <code>l_linger</code> is non-zero, <code>close()</code> will block until all the data is sent
     *                     or the time specified in <code>l_linger</code> elapses. If the socket is non-blocking,
     *                     <code>close()</code> will fail and return an error. Returns an array with two keps:
     *                     <code>l_onoff</code> and <code>l_linger</code>.
     *                     </p><p> <code>SO_OOBINLINE</code> - Reports whether the socket leaves out-of-band data
     *                     inline. Returns int.
     *                     </p><p> <code>SO_SNDBUF</code> - Reports the size of the send buffer. Returns int.
     *                     </p> <p><code>SO_RCVBUF</code> - Reports the size of the receive buffer. Returns int.
     *                     </p> p><code>SO_ERROR</code> - Reports information about error status and clears it.
     *                     Returns int.
     *                     </p> <p><code>SO_TYPE</code> - Reports the socket type (e.g. <code>SOCK_STREAM</code>).
     *                     Returns int.
     *                     </p> <p><code>SO_DONTROUTE</code> - Reports whether outgoing messages bypass the standard
     *                     routing facilities. Returns int.
     *                     </p><p><code>SO_RCVLOWAT</code> - Reports the minimum number of bytes to process for socket
     *                     input operations. Returns int.
     *                     </p><p><code>SO_RCVTIMEO</code> - Reports the timeout value for input operations.
     *                     Returns an array with two keys: <code>sec</code> which is the seconds part on the timeout
     *                     value and <code>usec</code> which is the microsecond part of the timeout value.
     *                     </p><p> <code>SO_SNDTIMEO</code> - Reports the timeout value specifying the amount of time
     *                     that an output function blocks because flow control prevents data from being sent. Returns
     *                     an array with two keys: <code>sec</code> which is the seconds part on the timeout value and
     *                     <code>usec</code> which is the microsecond part of the timeout value.</p>
     *                     <p><code>SO_SNDLOWAT</code> - Reports the minimum number of bytes to process for socket
     *                     output operations. Returns int.
     *                     </p><p><code>TCP_NODELAY</code> - Reports whether the Nagle TCP algorithm is disabled.
     *                     Returns int.
     *                     </p><p><code>IP_MULTICAST_IF</code> - The outgoing interface for IPv4 multicast packets.
     *                     Returns the index of the interface (int).
     *                     </p><p><code>IPV6_MULTICAST_IF</code> - The outgoing interface for IPv6 multicast
     *                     packets. Returns the same thing as <code>IP_MULTICAST_IF</code>.
     *                     </p><p><code>IP_MULTICAST_LOOP</code> - The multicast loopback policy for IPv4 packets, which
     *                     determines whether multicast packets sent by this socketalso reach receivers in the same host
     *                     that have joined the same multicast group on the outgoing interface used by this socket.
     *                     This is the case by default. Returns int.
     *                     </p><p><code>IPV6_MULTICAST_LOOP</code> - Analogous to <code>IP_MULTICAST_LOOP</code>,
     *                     but for IPv6. Returns int.</p><p><code>IP_MULTICAST_TTL</code> - The ime-to-live of outgoing
     *                     IPv4 multicast packets. This should be a value between 0 (don't leave the interface)
     *                     and 255. The default value is 1 (only the local network is reached). Returns int.</p><p>
     *                     <code>IPV6_MULTICAST_HOPS</code> - Analogous to <code>IP_MULTICAST_TTL</code>, but for IPv6 packets. The value -1
     *                     is also accepted, meaning the route default should be used. Returns int.</p>
     *
     * @return int|array|null See the descriptions based on the option being requested above.
     */
    public function getOption(int $level, int $optname): int|array|null
    {
        $this->checkSocketOpen();

        $return = socket_get_option($this->resource, $level, $optname);

        if ($return === false) {
            throw SocketException::new(tr('Failed to get socket option ":opt" at leve; :"level"', [
                ':opt'     => $optname,
                ':level'   => $level
            ]))
            ->setCode(socket_last_error($this->resource))
            ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $return;
    }


    /**
     * Sets the remote address and port
     *
     * @param SocketResource $socket
     *
     * @return static
     */
    protected function initializeRemoteName(SocketResource $socket): static
    {
        if ($this->isConnected()) {
            $result = socket_getpeername($socket, $this->remote_address, $this->remote_port);

            if (!$result) {
                throw SocketException::new(tr('Failed to get peer name for socket ":socket"', [
                    ':socket' => $this->resource,
                ]))
                ->setCode(socket_last_error($this->resource))
                ->addMessages(socket_strerror(socket_last_error($this->resource)));
            }

        } else {
            $this->remote_address = null;
            $this->remote_port    = null;
        }

        return $this;
    }


    /**
     * Sets the local address and port
     *
     * <p><b>Note:</b> <code>getSockName()</code> should not be used with <code>AF_UNIX</code> sockets created with
     * <code>connect()</code>. Only sockets created with <code>accept()</code> or a primary server socket following a
     * call to <code>bind()</code> will return meaningful values.</p>
     *
     * @param SocketResource $socket
     *
     * @return static
     */
    protected function initializeLocalName(SocketResource $socket): static
    {
        if ($this->isConnected()) {
            $result = socket_getsockname($socket, $this->local_address, $this->local_port);

            if (!$result) {
                throw SocketException::new(tr('Failed to get socket name for socket ":socket"', [
                    ':socket' => $this->resource,
                ]))
                ->setCode(socket_last_error($this->resource))
                ->addMessages(socket_strerror(socket_last_error($this->resource)));
            }

        } else {
            $this->local_address = null;
            $this->local_port    = null;
        }

        return $this;
    }


    /**
     * Imports a stream.
     *
     * <p>Imports a stream that encapsulates a socket into a socket extension resource.</p>
     *
     * @param resource $stream The stream resource to import.
     *
     * @return static Returns a Socket object based on the stream.
     *
     * @throws SocketException If the import of the stream is not successful.
     *
     */
    public static function importStream($stream): static
    {
        throw new UnderConstructionException();

        if (get_resource_type($stream) === 'Unknown') {
            throw new SocketException(tr('Invalid stream type: ":stream"', [
                ':stream' => $stream
            ]));
        }

        $result = socket_import_stream($stream);

        if (!$result) {
            throw SocketException::new(tr('Failed to import socket at stream ":stream"', [
                ':stream' => $stream
            ]));
        }

        return new static($result, true);
    }


    /**
     * Listens for a connection on a socket.
     *
     * <p>After the socket has been created using <code>create()</code> and bound to a name with <code>bind()</code>,
     * it may be told to listen for incoming connections on socket.</p>
     *
     * @param int $backlog
     *
     * @return static Returns <code>true</code> on success.
     */
    public function listen(int $backlog = 0): static
    {
        Log::action(tr('Listening on ":ip::port"', [
            ':ip'   => $this->getLocalAddress(),
            ':port' => $this->getLocalPort()
        ]), 1);

        $result = socket_listen($this->resource, $backlog);

        if (!$result) {
            throw SocketException::new(tr('Socket failed to listen'))
                                 ->setCode(socket_last_error($this->resource))
                                 ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $this;
    }


    /**
     * Reads a maximum of length bytes from a socket.
     *
     * <p>Reads from the socket created by the <code>create()</code> or <code>accept()</code> functions.</p>
     *
     * @param int $length <p>The maximum number of bytes read is specified by the length parameter. Otherwise you can
     *                    use <code>\r</code>, <code>\n</code>, or <code>\0</code> to end reading (depending on the type
     *                    parameter, see below).</p>
     * @param int $type   <p>(Optional) type parameter is a named constant:<ul><li><code>PHP_BINARY_READ</code>
     *                    (Default) - use the system <code>recv()</code> function. Safe for reading binary data.
     *                    </li><li> <code>PHP_NORMAL_READ</code> - reading stops at <code>\n</code> or <code>\r</code>.
     *                    </li></ul></p>
     *
     * @return string Returns the data as a string. Returns a zero length string ("") when there is no more data to
     *                read.
     *
     * @throws SocketException If there was an error reading or if the host closed the connection.
     *
     * @see PhoSocket::accept()
     * @see PhoSocket::create()
     */
    public function read(int $length, int $type = PHP_BINARY_READ): string
    {
        $this->checkSocketOpen();
        $return = socket_read($this->resource, $length, $type);

        if ($return === false) {
            if (!$this->isConnected()) {
                throw SocketDisconnectedException::new(tr('Cannot read from socket ":address::port", it is not connected', [
                    ':address' => $this->getLocalAddress(),
                    ':port'    => $this->getLocalPort()
                ]));
            }

            throw SocketException::new(tr('Failed to read from socket with length ":length" and type ":type"', [
                ':length' => $length,
                ':type'   => $type
            ]))
            ->setCode(socket_last_error($this->resource))
            ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $return;
    }


    /**
     * Receives data from a connected socket.
     *
     * <p>Receives length bytes of data in buffer from the socket. <code>receive()</code> can be used to gather data
     * from connected sockets. Additionally, one or more flags can be specified to modify the behaviour of the
     * function.</p><p>buffer is passed by reference, so it must be specified as a variable in the argument list. Data
     * read from socket by <code>receive()</code> will be returned in buffer.</p>
     *
     * @param string $buffer <p>The data received will be fetched to the variable specified with buffer. If an error
     *                       occurs, if the connection is reset, or if no data is available, buffer will be set to
     *                       <code>NULL</code>.</p>
     * @param int $length    Up to length bytes will be fetched from remote host.
     * @param int $flags     <p>The value of flags can be any combination of the following flags, joined with the binary
     *                       OR (<code>|</code>) operator.<ul><li><code>MSG_OOB</code> - Process out-of-band data.</li>
     *                       <li><code>MSG_PEEK</code> - Receive data from the beginning of the receive queue without
     *                       removing it from the queue.</li><li> <code>MSG_WAITALL</code> - Block until at least length
     *                       are received. However, if a signal is caught or the remote host disconnects, the function
     *                       may return less data.</li> <li><code>MSG_DONTWAIT</code> - With this flag set, the function
     *                       returns even if it would normally have blocked. </li></ul></p>
     *
     * @return int Returns the number of bytes received.
     *
     * @throws SocketException If there was an error receiving data.
     *
     */
    public function receive(string &$buffer, int $length, int $flags): int
    {
        $return = socket_recv($this->resource, $buffer, $length, $flags);

        if ($return === false) {
            throw SocketException::new(tr('Failed to receive from socket with buffer ":buffer" and length ":length" and flags ":flags"', [
                ':buffer' => $buffer,
                ':length' => $length,
                ':flags'  => $flags
            ]))
            ->setCode(socket_last_error($this->resource))
            ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $return;
    }


    /**
     * Runs the select() system call on the given arrays of sockets with a specified timeout.
     *
     * <p>accepts arrays of sockets and waits for them to change status. Those coming with BSD sockets background will
     * recognize that those socket resource arrays are in fact the so-called file descriptor sets. Three independent
     * arrays of socket resources are watched.</p><p><b>WARNING:</b> On exit, the arrays are modified to indicate which
     * socket resource actually changed status.</p><p>ou do not need to pass every array to <code>select()</code>. You
     * can leave it out and use an empty array or <code>NULL</code> instead. Also do not forget that those arrays are
     * passed by reference and will be modified after <code>select()</code> returns.
     *
     * @param PhoSocket[] &$read                 <p>The sockets listed in the read array will be watched to see if
     *                                           characters become available for reading (more precisely, to see if a
     *                                           read will not block - in particular, a socket resource is also ready on
     *                                           end-of-file, in which case a <code>read()</code> will return a zero
     *                                           length string).</p>
     * @param PhoSocket[] &$write                The sockets listed in the write array will be watched to see if a write
     *                                           will not block.
     * @param array        $except               The sockets listed in the except array will be watched for exceptions.
     * @param ?int         $timeout_seconds      The seconds portion of the timeout parameters (in conjunction with
     *                                           timeoutMilliseconds). The timeout is an upper bound on the amount of
     *                                           time elapsed before <code>select()</code> returns. timeoutSeconds may
     *                                           be zero, causing the <code>select()</code> to return immediately. This
     *                                           is useful for polling. If timeoutSeconds is <code>NULL</code>
     *                                           (no timeout), the <code>select()</code> can block indefinitely.</p>
     * @param int          $timeout_milliseconds See the description for timeoutSeconds.
     *
     * @return int Returns the number of socket resources contained in the modified arrays, which may be zero if the
     *             timeout expires before anything interesting happens.
     */
    public static function select(array &$read, array &$write, array &$except, ?int $timeout_seconds, int $timeout_milliseconds = 0): int {
        $read_sockets   = static::mapClassToRawSocket($read);
        $write_sockets  = static::mapClassToRawSocket($write);
        $except_sockets = static::mapClassToRawSocket($except);

        $return = socket_select(
            $read_sockets,
            $write_sockets,
            $except_sockets,
            $timeout_seconds,
            $timeout_milliseconds
        );

        if ($return === false) {
            throw SocketException::new(tr('Failed to select socket'));
        }

        $read   = $read_sockets   ? static::mapRawSocketToClass($read_sockets)   : [];
        $write  = $write_sockets  ? static::mapRawSocketToClass($write_sockets)  : [];
        $except = $except_sockets ? static::mapRawSocketToClass($except_sockets) : [];

        return $return;
    }


    /**
     * Maps an array of Sockets to an array of socket resources.
     *
     * @param PhoSocket[] $sockets An array of sockets to map.
     *
     * @return SocketResource[] Returns the corresponding array of resources.
     */
    protected static function mapClassToRawSocket(array $sockets): array
    {
        $result = [];

        foreach ($sockets as $socket) {
            if ($socket->getResource()) {
                $result[] = $socket->resource;
            }
        }

        return $result;
    }


    /**
     * Maps an array of socket resources to an array of Sockets.
     *
     * @param SocketResource[] $sockets An array of socket resources to map.
     *
     * @return PhoSocket[] Returns the corresponding array of Socket objects.
     */
    protected static function mapRawSocketToClass(array $sockets): array
    {
        $result = [];

        foreach ($sockets as $raw_socket) {
            $socket_hash = spl_object_hash($raw_socket);

            if (isset(static::$source[$socket_hash])) {
                $result[] = static::$source[$socket_hash];
            }
        }

        return $result;
    }


    /**
     * Write to a socket.
     *
     * <p>The function <code>write()</code> writes to the socket from the given buffer.</p>
     *
     * @param string $buffer   The buffer to be written.
     * @param ?int $length     The optional parameter length can specify an alternate length of bytes written to the
     *                         socket. If this length is greater than the buffer length, it is silently truncated to the
     *                         length of the buffer.
     *
     * @return int Returns the number of bytes successfully written to the socket.
     *
     * @throws SocketException If there was a failure.
     *
     */
    public function write(string $buffer, int $length = null): int
    {
        if (!$this->isConnected()) {
            throw SocketDisconnectedException::new(tr('Cannot read from socket ":address::port", it is not connected', [
                ':address' => $this->getLocalAddress(),
                ':port'    => $this->getLocalPort()
            ]));
        }

        if ($length === null) {
            $length = strlen($buffer);
        }

        if ($length === 0) {
            return 0;
        }

        $total_written   = 0;
        $original_length = $length;

        try {
            while ($length > 0) {
                $written = socket_write($this->resource, $buffer, $length);

                if ($written === false) {
                    throw SocketException::new(tr('PHP socket socket_write failed'));
                }

                $total_written += $written;

                if ($total_written >= $original_length) {
                    break;
                }

                $buffer = substr($buffer, $written);
                $length -= $written;
            }

            if ($total_written !== $original_length) {
                throw SocketException::new(tr('incomplete write'));
            }

        } catch (Throwable $e) {
            throw SocketException::new(tr('Failed to write to socket with buffer ":buffer" and length ":length"', [
                ':buffer' => $buffer,
                ':length' => $length
            ]), $e)
            ->setCode(socket_last_error($this->resource))
            ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $total_written;
    }


    /**
     * Sends data to a connected socket.
     *
     * <p>Sends length bytes to the socket from buffer.</p>
     *
     * @param string  $buffer  A buffer containing the data that will be sent to the remote host.
     * @param int     $flags   <p>The value of flags can be any combination of the following flags, joined with the
     *                         binary OR (<code>|</code>) operator.<ul><li><code>MSG_OOB</code> - Send OOB (out-of-band)
     *                         data.</li><li> <code>MSG_EOR</code> - Indicate a record mark. The sent data completes the
     *                         record.</li> <li><code>MSG_EOF</code> - Close the sender side of the socket and include
     *                         an appropriate notification of this at the end of the sent data. The sent data completes
     *                         the transaction.</li> <li><code>MSG_DONTROUTE</code> - Bypass routing, use direct
     *                         interface.</li></ul></p>
     * @param int|null $length The number of bytes that will be sent to the remote host from buffer.
     *
     * @return int Returns the number of bytes sent.
     */
    public function send(string $buffer, int $flags = 0, int $length = null): int
    {
        if ($length === null) {
            $length = strlen($buffer);
        }

        while (true) {
            $return = socket_send($this->resource, $buffer, $length, $flags);

            if ($return === false) {
                throw SocketException::new(tr('Failed to send data to socket with buffer ":buffer" and length ":length" and flags ":flags"', [
                    ':buffer' => $buffer,
                    ':length' => $length,
                    ':flags'  => $flags
                ]))
                ->setCode(socket_last_error($this->resource))
                ->addMessages(socket_strerror(socket_last_error($this->resource)));
            }

            if ($return < $length) {
                $buffer = substr($buffer, $return);
                $length -= $return;

            } else {
                break;
            }
        }

        return $return;
    }


    /**
     *
     * Set the socket to blocking / non blocking.
     *
     * <p>Removes (blocking) or set (non blocking) the <code>O_NONBLOCK</code> flag on the socket.</p><p>When an
     * operation is performed on a blocking socket, the script will pause its execution until it receives a signal or it
     * can perform the operation.</p><p>When an operation is performed on a non-blocking socket, the script will not
     * pause its execution until it receives a signal or it can perform the operation. Rather, if the operation would
     * result in a block, the called function will fail.</p>
     *
     * @param bool $bool Flag to indicate if the Socket should block (<code>true</code>) or not block
     *                   (<code>false</code>).
     * @throws SocketException
     */
    public function setBlocking(bool $bool): static
    {
        $result = $bool ? socket_set_block($this->resource): socket_set_nonblock($this->resource);

        if ($result === false) {
            throw SocketException::new(tr('Failed to set blocking to ":bool"', [
                ':bool' => $bool
            ]))
            ->setCode(socket_last_error($this->resource))
            ->addMessages(socket_strerror(socket_last_error($this->resource)));
        }

        return $this;
    }


    /**
     * Checks if a socket is available and open, throws exception otherwise
     *
     * @return void
     */
    public function checkSocketOpen(): void
    {
        if ($this->getResource()) {
            return;
        }

        throw new SocketException(tr('Socket is not connected', [
            ':resource' => $this->resource
        ]));
    }


    /**
     * Shuts down this socket
     *
     * @return void
     */
    public function shutdown(): void
    {
        socket_shutdown($this->getResource());
    }
}
