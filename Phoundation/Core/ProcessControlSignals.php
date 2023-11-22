<?php

declare(strict_types=1);

namespace Phoundation\Core;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log\Log;
use Phoundation\Date\Time;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;


/**
 * Class ProcessControlSignals
 *
 * This class handles process control signals. It allows for user defined handling of each type of signal,
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class ProcessControlSignals
{
    /**
     * The list of signal handlers
     *
     * @var array $signals
     */
    protected static array $signals;

    /**
     * The instance
     *
     * @var ProcessControlSignals $instance
     */
    protected static ProcessControlSignals $instance;


    /**
     * ProcessControlSignals constructor
     */
    protected function __construct()
    {
        static::init();
    }


    /**
     * Singleton, ensure to always return the same ProcessControlSignals object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Set the specified callback for the specified signal
     *
     * @param int $signal
     * @param string $name
     * @param int|null $exit_code
     * @param callable $callback
     * @return void
     */
    public static function setSignal(int $signal, string $name, ?int $exit_code, callable $callback): void
    {
        static::getInstance();

        static::$signals[$signal] = [
            'name'      => $name,
            'exit_code' => $exit_code,
            'callback'  => $callback,
        ];
    }


    /**
     * Returns the callback for the specified signal
     *
     * @param int $signal
     * @return array|null
     */
    public static function getSignal(int $signal): ?array
    {
        static::getInstance();

        if (array_key_exists($signal, static::$signals)) {
            return static::$signals[$signal];
        }

        return null;
    }


    /**
     * Handle process signals
     *
     * @param int $signal
     * @param mixed $info
     * @return void
     */
    public static function execute(int $signal, mixed $info = null): void
    {
        Log::warning(tr('Received process signal ":signal"', [':signal' => $signal]), 10);
        static::getInstance();

        if (!array_key_exists($signal, static::$signals)) {
            throw new OutOfBoundsException(tr('Unknown process signal ":signal" received', [
                ':signal' => $signal
            ], $info));
        }

        if (static::$signals[$signal]['callback']) {
            // Only execute callbacks if defined
            static::$signals[$signal]['callback'](static::$signals[$signal]['name'], $info, static::$signals[$signal]['exit_code']);
        }
    }


    /**
     * Terminate the process due to a process signal
     *
     * @param string $signal
     * @param mixed $info
     * @param int $exit_code
     * @return never
     */
    #[NoReturn] protected static function dumpTerminate(string $signal, mixed $info, int $exit_code): never
    {
        // The SIGTERM signal is sent to a process to request its termination. Unlike the SIGKILL signal, it can be caught and interpreted or ignored by the process. This allows the process to perform nice termination releasing resources and saving state if appropriate. SIGINT is nearly identical to SIGTERM.
        Log::warning(tr('Killing process because of process signal ":signal"', [':signal' => $signal]), 10);
        Log::backtrace();
        Log::warning(tr('Signal information:'), 10);
        Log::table($info);
        Core::exit($exit_code, tr('Script ":script" was terminated because of signal ":signal" with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
            ':signal'   => $signal,
            ':script'   => Strings::from(Core::readRegister('system', 'script'), DIRECTORY_ROOT),
            ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
            ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
            ':exitcode' => $exit_code
        ]));
    }


    /**
     * Initializes the signal handling array with default handlers for each known signal
     *
     * @note Comments are taken from wikipedia.org, see https://en.wikipedia.org/wiki/Signal_(IPC)#POSIX_signals
     * @return void
     */
    protected function init(): void
    {
        if (isset(static::$signals)) {
            return;
        }

        $default_handler = function (string $signal, mixed $info, int $exit_code) {
            static::dumpTerminate($signal, $info, $exit_code);
        };

        static::$signals = [
            // The SIGKILL signal is sent to a process to cause it to terminate immediately (kill). In contrast to SIGTERM and SIGINT, this signal cannot be caught or ignored, and the receiving process cannot perform any clean-up upon receiving this signal. The following exceptions apply:,
            // Zombie processes cannot be killed since they are already dead and waiting for their parent processes to reap them.
            // Processes that are in the blocked state will not die until they wake up again.
            // The init process is special => [], It does not get signals that it does not want to handle, and thus it can ignore SIGKILL.[7] An exception from this exception is while init is ptraced on Linux.[8][9]
            // An uninterruptibly sleeping process may not terminate (and free its resources) even when sent SIGKILL. This is one of the few cases in which a UNIX system may have to be rebooted to solve a temporary software problem.
            // SIGKILL is used as a last resort when terminating processes in most system shutdown procedures if it does not voluntarily exit in response to SIGTERM. To speed the computer shutdown procedure, Mac OS X 10.6, aka Snow Leopard, will send SIGKILL to applications that have marked themselves "clean" resulting in faster shutdown times with, presumably, no ill effects.[10] The command killall -9 has a similar, while dangerous effect, when executed e.g. in Linux; it doesn\'t let programs save unsaved data. It has other options, and with none, uses the safer SIGTERM signal.')
            // CANNOT BE CAUGHT
            SIGKILL => [
                'name'      => 'SIGKILL',
                'exit_code' => 1,
                'callback'  => null,
            ],

            // The SIGTERM signal is sent to a process to request its termination. Unlike the SIGKILL signal, it can be caught and interpreted or ignored by the process. This allows the process to perform nice termination releasing resources and saving state if appropriate. SIGINT is nearly identical to SIGTERM.
            SIGTERM => [
                'name'      => 'SIGTERM',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGINT signal is sent to a process by its controlling terminal when a user wishes to interrupt the process. This is typically initiated by pressing Ctrl+C, but on some systems, the "delete" character or "break" key can be used.[12]
            SIGINT => [
                'name'      => 'SIGINT',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGABRT and SIGIOT signals are sent to a process to tell it to abort, i.e. to terminate. The signal is usually initiated by the process itself when it calls abort() function of the C Standard Library, but it can be sent to the process from outside like any other signal.
            SIGABRT => [
                'name'      => 'SIGABRT',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
            SIGIOT => [
                'name'      => 'SIGIOT',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGALRM, SIGVTALRM and SIGPROF signal is sent to a process when the time limit specified in a call to a preceding alarm setting function (such as setitimer) elapses. SIGALRM is sent when real or clock time elapses. SIGVTALRM is sent when CPU time used by the process elapses. SIGPROF is sent when CPU time used by the process and by the system on behalf of the process elapses.
            SIGALRM => [
                'name'      => 'SIGALRM',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
            SIGVTALRM => [
                'name'      => 'SIGVTALRM',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
            SIGPROF => [
                'name'      => 'SIGPROF',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGBUS signal is sent to a process when it causes a bus error. The conditions that lead to the signal being sent are, for example, incorrect memory access alignment or non-existent physical address.
            SIGBUS => [
                'name'      => 'SIGBUS',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGCHLD signal is sent to a process when a child process terminates, is interrupted, or resumes after being interrupted. One common usage of the signal is to instruct the operating system to clean up the resources used by a child process after its termination without an explicit call to the wait system call.
            SIGCHLD => [
                'name'      => 'SIGCHLD',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // The SIGCONT signal instructs the operating system to continue (restart) a process previously paused by the SIGSTOP or SIGTSTP signal. One important use of this signal is in job control in the Unix shell.
            SIGCONT => [
                'name'      => 'SIGCONT =>',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // The SIGFPE signal is sent to a process when it executes an erroneous arithmetic operation, such as division by zero. This may include integer division by zero, and integer overflow in the result of a divide (only INT_MIN/-1, INT64_MIN/-1 and %-1 accessible from C).[2][3].
            SIGFPE => [
                'name'      => 'SIGFPE',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGHUP signal is sent to a process when its controlling terminal is closed. It was originally designed to notify the process of a serial line drop (a hangup). In modern systems, this signal usually means that the controlling pseudo or virtual terminal has been closed.[4] Many daemons will reload their configuration files and reopen their logfiles instead of exiting when receiving this signal.[5] nohup is a command to make a command ignore the signal.
            SIGHUP => [
                'name'      => 'SIGHUP',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGILL signal is sent to a process when it attempts to execute an illegal, malformed, unknown, or privileged instruction.
            SIGILL => [
                'name'      => 'SIGILL',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGPIPE signal is sent to a process when it attempts to write to a pipe without a process connected to the other end.
            SIGPIPE => [
                'name'      => 'SIGPIPE',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGPOLL signal is sent when an event occurred on an explicitly watched file descriptor.[11] Using it effectively leads to making asynchronous I/O requests since the kernel will poll the descriptor in place of the caller. It provides an alternative to active polling.
            SIGPOLL => [
                'name'      => 'SIGPOLL',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGRTMIN to SIGRTMAX signals are intended to be used for user-defined purposes. They are real-time signals.
            SIGRTMIN => [
                'name'      => 'SIGRTMIN',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
            SIGRTMAX => [
                'name'      => 'SIGRTMAX',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGQUIT signal is sent to a process by its controlling terminal when the user requests that the process quit and perform a core dump.
            SIGQUIT => [
                'name'      => 'SIGQUIT',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGSEGV signal is sent to a process when it makes an invalid virtual memory reference, or segmentation fault, i.e. when it performs a segmentation violation.[12]
            SIGSEGV => [
                'name'      => 'SIGSEGV',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGSTOP signal instructs the operating system to stop a process for later resumption.
            // CANNOT BE CAUGHT
            SIGSTOP => [
                'name'      => 'SIGSTOP',
                'exit_code' => 200,
                'callback'  => null,
            ],

            // The SIGSYS signal is sent to a process when it passes a bad argument to a system call. In practice, this kind of signal is rarely encountered since applications rely on libraries (e.g. libc) to make the call for them. SIGSYS can be received by applications violating the Linux Seccomp security rules configured to restrict them.
            SIGSYS => [
                'name'      => 'SIGSYS',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGTSTP signal is sent to a process by its controlling terminal to request it to stop (terminal stop). It is commonly initiated by the user pressing Ctrl+Z. Unlike SIGSTOP, the process can register a signal handler for, or ignore, the signal.
            SIGTSTP => [
                'name'      => 'SIGTSTP',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // The SIGTTIN and SIGTTOU signals are sent to a process when it attempts to read in or write out respectively from the tty while in the background. Typically, these signals are received only by processes under job control; daemons do not have controlling terminals and, therefore, should never receive these signals.
            SIGTTIN => [
                'name'      => 'SIGTTIN',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],
            SIGTTOU => [
                'name'      => 'SIGTTOU',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // The SIGTRAP signal is sent to a process when an exception (or trap) occurs => [], a condition that a debugger has requested to be informed of – for example, when a particular function is executed, or when a particular variable changes value.
            SIGTRAP => [
                'name'      => 'SIGTRAP',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGURG signal is sent to a process when a socket has urgent or out-of-band data available to read.
            SIGURG => [
                'name'      => 'SIGURG',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // The SIGUSR1 and SIGUSR2 signals are sent to a process to indicate user-defined conditions.
            SIGUSR1 => [
                'name'      => 'SIGUSR1',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
            SIGUSR2 => [
                'name'      => 'SIGUSR2',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGXCPU signal is sent to a process when it has used up the CPU for a duration that exceeds a certain predetermined user-settable value.[13] The arrival of a SIGXCPU signal provides the receiving process a chance to quickly save any intermediate results and to exit gracefully, before it is terminated by the operating system using the SIGKILL signal.
            SIGXCPU => [
                'name'      => 'SIGXCPU',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGXFSZ signal is sent to a process when it grows a file that exceeds the maximum allowed size.
            SIGXFSZ => [
                'name'      => 'SIGXFSZ',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],

            // The SIGWINCH signal is sent to a process when its controlling terminal changes its size (a window change).
            SIGWINCH => [
                'name'      => 'SIGWINCH',
                'exit_code' => 200,
                'callback'  => function(mixed $info) {},
            ],

            // Unknown signal handler
            null => [
                'name'      => '',
                'exit_code' => 200,
                'callback'  => $default_handler,
            ],
        ];
    }
}
