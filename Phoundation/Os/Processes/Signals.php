<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Signals
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Signals
{
    /**
     * The known process signals
     *
     * @var array|string[] $signals
     */
    protected static array $signals = [
        1  => 'SIGHUP',
        2  => 'SIGINT',
        3  => 'SIGQUIT',
        4  => 'SIGILL',
        5  => 'SIGTRAP',
        6  => 'SIGABRT',
        7  => 'SIGBUS',
        8  => 'SIGFPE',
        9  => 'SIGKILL',
        10 => 'SIGUSR1',
        11 => 'SIGSEGV',
        12 => 'SIGUSR2',
        13 => 'SIGPIPE',
        14 => 'SIGALRM',
        15 => 'SIGTERM',
        16 => 'SIGSTKFLT',
        17 => 'SIGCHLD',
        18 => 'SIGCONT',
        19 => 'SIGSTOP',
        20 => 'SIGTSTP',
        21 => 'SIGTTIN',
        22 => 'SIGTTOU',
        23 => 'SIGURG',
        24 => 'SIGXCPU',
        25 => 'SIGXFSZ',
        26 => 'SIGVTALRM',
        27 => 'SIGPROF',
        28 => 'SIGWINCH',
        29 => 'SIGIO',
        30 => 'SIGPWR',
        31 => 'SIGSYS',
        34 => 'SIGRTMIN',
        35 => 'SIGRTMIN+1',
        36 => 'SIGRTMIN+2',
        37 => 'SIGRTMIN+3',
        38 => 'SIGRTMIN+4',
        39 => 'SIGRTMIN+5',
        40 => 'SIGRTMIN+6',
        41 => 'SIGRTMIN+7',
        42 => 'SIGRTMIN+8',
        43 => 'SIGRTMIN+9',
        44 => 'SIGRTMIN+10',
        45 => 'SIGRTMIN+11',
        46 => 'SIGRTMIN+12',
        47 => 'SIGRTMIN+13',
        48 => 'SIGRTMIN+14',
        49 => 'SIGRTMIN+15',
        50 => 'SIGRTMAX-14',
        51 => 'SIGRTMAX-13',
        52 => 'SIGRTMAX-12',
        53 => 'SIGRTMAX-11',
        54 => 'SIGRTMAX-10',
        55 => 'SIGRTMAX-9',
        56 => 'SIGRTMAX-8',
        57 => 'SIGRTMAX-7',
        58 => 'SIGRTMAX-6',
        59 => 'SIGRTMAX-5',
        60 => 'SIGRTMAX-4',
        61 => 'SIGRTMAX-3',
        62 => 'SIGRTMAX-2',
        63 => 'SIGRTMAX-1',
        64 => 'SIGRTMAX',
    ];


    /**
     * Throws an exception if the specified signal does not exist
     *
     * @param int|null $signal
     *
     * @return int|null
     */
    public static function check(?int $signal): ?int
    {
        if (!static::exists($signal)) {
            throw new OutOfBoundsException(tr('The specified signal ":signal" does not exist', [
                ':signal' => $signal,
            ]));
        }

        return $signal;
    }


    /**
     * Returns true if the specified signal exists
     *
     * @param int|null $signal
     *
     * @return bool
     */
    public static function exists(?int $signal): bool
    {
        if ($signal === null) {
            return true;
        }

        return array_key_exists($signal, static::$signals);
    }


    /**
     * Returns a list of all known process signals
     *
     * @return string[]
     */
    public static function get(): array
    {
        return static::$signals;
    }
}
