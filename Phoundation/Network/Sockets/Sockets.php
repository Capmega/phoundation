<?php

/**
 * Class Sockets
 *
 * This class manages multiple Socket objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Network\Sockets;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Network\Sockets\Exception\SocketException;


class Sockets extends Iterator
{
    /**
     * Sockets class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source)
              ->setAcceptedDataTypes(SocketInterface::class);
    }


    /**
     * Maps an array of socket resources to an array of Sockets.
     *
     * @param PhoSocket[] $sockets An array of socket resources to map.
     *
     * @return PhoSocket[] Returns the corresponding array of Socket objects.
     */
    protected function mapRawSocketToClass(array $sockets): array
    {
        return array_map(
            static function ($rawSocket) {
                return static::$source[spl_object_hash($rawSocket)];
            },
            $sockets
        );
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
     * @param PhoSocket[] &$read                <p>The sockets listed in the read array will be watched to see if characters become
     *                                       available for reading (more precisely, to see if a read will not block - in particular, a socket resource is
     *                                       also ready on end-of-file, in which case a <code>read()</code> will return a zero length string).</p>
     * @param PhoSocket[] &$write               The sockets listed in the write array will be watched to see if a write will not block.
     * @param PhoSocket[] &$except              he sockets listed in the except array will be watched for exceptions.
     * @param ?int      $timeoutSeconds      The seconds portion of the timeout parameters (in conjunction with
     *                                       timeoutMilliseconds). The timeout is an upper bound on the amount of time elapsed before <code>select()</code>
     *                                       returns. timeoutSeconds may be zero, causing the <code>select()</code> to return immediately. This is useful
     *                                       for polling. If timeoutSeconds is <code>NULL</code> (no timeout), the <code>select()</code> can block
     *                                       indefinitely.</p>
     * @param int       $timeoutMilliseconds See the description for timeoutSeconds.
     *
     * @return int Returns the number of socket resources contained in the modified arrays, which may be zero if the
     *             timeout expires before anything interesting happens.
     * @throws SocketException If there was an error.
     *
     */
    public function select(
        array &$read,
        array &$write,
        array &$except,
        ?int  $timeoutSeconds,
        int   $timeoutMilliseconds = 0
    ): int
    {
        $readSockets = static::mapClassToRawSocket($read);
        $writeSockets = static::mapClassToRawSocket($write);
        $exceptSockets = static::mapClassToRawSocket($except);

        $return = socket_select(
            $readSockets,
            $writeSockets,
            $exceptSockets,
            $timeoutSeconds,
            $timeoutMilliseconds
        );

        if ($return === false) {
            throw new SocketException();
        }

        $read = [];
        $write = [];
        $except = [];

        if ($readSockets) {
            $read = static::mapRawSocketToClass($readSockets);
        }
        if ($writeSockets) {
            $write = static::mapRawSocketToClass($writeSockets);
        }
        if ($exceptSockets) {
            $except = static::mapRawSocketToClass($exceptSockets);
        }

        return $return;
    }


    /**
     * Maps an array of Sockets to an array of socket resources.
     *
     * @param PhoSocket[] $sockets An array of sockets to map.
     *
     * @return PhoSocket[] Returns the corresponding array of resources.
     */
    protected static function mapClassToRawSocket(array $sockets): array
    {
        return array_filter(
            array_map(
                static function (PhoSocket $socket) {
                    return $socket.getResource();
                },
                $sockets
            )
        );
    }
}
