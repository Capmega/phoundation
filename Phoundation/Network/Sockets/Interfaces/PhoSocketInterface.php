<?php

namespace Phoundation\Network\Sockets\Interfaces;

use Phoundation\Network\Enums\EnumNetworkSocketDomain;
use Phoundation\Network\Sockets\Exception\SocketException;
use Socket;

interface PhoSocketInterface
{
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
    public function accept(): static;


    /**
     * Binds a name to a socket.`
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
    public function bind(string $address, int $port = 0): static;


    /**
     * Closes a PhoSocket and unsets it from the source array
     *
     * @param int  $linger
     * @param bool $force
     *
     * @return PhoSocket
     */
    public function disconnect(int $linger = 0, bool $force = false): static;


    /**
     * Connect to a socket.
     *
     * <p>Initiate a connection to the address given using the current php socket resource, which must be a valid socket
     * resource created with <code>create()</code>.
     *
     * @return static
     *
     * @see PhoSocket::listen()
     * @see PhoSocket::create()
     * @see PhoSocket::bind()
     */
    public function connect(): static;


    /**
     * Returns the Domain property of this PhoSocket
     *
     * @return EnumNetworkSocketDomain
     */
    public function getDomain(): EnumNetworkSocketDomain;


    /**
     * Sets the Domain property of this PhoSocket
     *
     * @param EnumNetworkSocketDomain $domain
     *
     * @return static
     */
    public function setDomain(EnumNetworkSocketDomain $domain): static;


    /**
     * Returns the Type property of this PhoSocket
     *
     * @return int
     */
    public function getType(): int;


    /**
     * Sets the Type property of this PhoSocket
     *
     * @param int $type
     *
     * @return static
     */
    public function setType(int $type): static;


    /**
     * Returns the Protocol property of this PhoSocket
     *
     * @return int
     */
    public function getProtocol(): int;


    /**
     * Sets the Protocol property of this PhoSocket
     *
     * @param int $protocol
     *
     * @return static
     */
    public function setProtocol(int $protocol): static;


    /**
     * Returns the Resource property of this PhoSocket
     *
     * @return Socket
     */
    public function getResource(): Socket;


    /**
     * Sets the Resource property of this PhoSocket
     *
     * @param Socket $resource
     *
     * @return static
     */
    public function setResource(Socket $resource): static;


    /**
     * Returns the Port value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return int|null
     */
    public function getLocalPort(): ?int;


    /**
     * Returns the Address value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return string|null
     */
    public function getLocalAddress(): ?string;


    /**
     * Returns the Port value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return int|null
     */
    public function getRemotePort(): ?int;


    /**
     * Returns the Address value for the PhoSocket, if initialized, otherwise returns null
     *
     * @return string|null
     */
    public function getRemoteAddress(): ?string;


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
    public function getOption(int $level, int $optname): int|array|null;


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
    public function listen(int $backlog = 0): static;


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
    public function read(int $length, int $type = PHP_BINARY_READ): string;


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
     * @param int    $length Up to length bytes will be fetched from remote host.
     * @param int    $flags  <p>The value of flags can be any combination of the following flags, joined with the binary
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
    public function receive(string &$buffer, int $length, int $flags): int;


    /**
     * Write to a socket.
     *
     * <p>The function <code>write()</code> writes to the socket from the given buffer.</p>
     *
     * @param string $buffer   The buffer to be written.
     * @param ?int   $length   The optional parameter length can specify an alternate length of bytes written to the
     *                         socket. If this length is greater than the buffer length, it is silently truncated to the
     *                         length of the buffer.
     *
     * @return int Returns the number of bytes successfully written to the socket.
     *
     * @throws SocketException If there was a failure.
     *
     */
    public function write(string $buffer, int $length = null): int;


    /**
     * Sends data to a connected socket.
     *
     * <p>Sends length bytes to the socket from buffer.</p>
     *
     * @param string   $buffer A buffer containing the data that will be sent to the remote host.
     * @param int      $flags  <p>The value of flags can be any combination of the following flags, joined with the
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
    public function send(string $buffer, int $flags = 0, int $length = null): int;


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
     *
     * @throws SocketException
     */
    public function setBlocking(bool $bool): static;


    /**
     * Checks if a socket is available and open, throws exception otherwise
     *
     * @return void
     */
    public function checkSocketOpen(): void;


    /**
     * Shuts down this socket
     *
     * @return void
     */
    public function shutdown(): void;
}
