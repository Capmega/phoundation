<?php

/**
 * Enum EnumSignal
 *
 * This enum defines available POSIX reliable signals and POSIX real-time signals
 *
 * Signal dispositions (actions)
 *
 * Term   Default action is to terminate the process.
 * Ign    Default action is to ignore the signal.
 * Core   Default action is to terminate the process and dump core
 * Stop   Default action is to stop the process.
 * Cont   Default action is to continue the process if it is currently stopped.
 *
 * @see       https://www.man7.org/linux/man-pages/man7/signal.7.html
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Enums;


enum EnumSignal: int
{
    case SIGHUP     = 1;     //
    case SIGINT     = 2;     //
    case SIGQUIT    = 3;     //
    case SIGILL     = 4;     //
    case SIGTRAP    = 5;     //
    case SIGABRT    = 6;     // Core
    case SIGBUS     = 7;     // Term
    case SIGFPE     = 8;     //
    case SIGKILL    = 9;     //
    case SIGUSR1    = 10;    //
    case SIGSEGV    = 11;    //
    case SIGUSR2    = 12;    //
    case SIGPIPE    = 13;    //
    case SIGALRM    = 14;    // Term
    case SIGTERM    = 15;    //
    case SIGSTKFLT  = 16;    //
    case SIGCHLD    = 17;    // Ign
    case SIGCONT    = 18;    // Cont
    case SIGSTOP    = 19;    //
    case SIGTSTP    = 20;    //
    case SIGTTIN    = 21;    //
    case SIGTTOU    = 22;    //
    case SIGURG     = 23;    //
    case SIGXCPU    = 24;    //
    case SIGXFSZ    = 25;    //
    case SIGVTALRM  = 26;    //
    case SIGPROF    = 27;    //
    case SIGWINCH   = 28;    //
    case SIGIO      = 29;    //
    case SIGPWR     = 30;    //
    case SIGSYS     = 31;    //
    case SIGRTMIN   = 34;    //
    case SIGRTMIN1  = 35;    //
    case SIGRTMIN2  = 36;    //
    case SIGRTMIN3  = 37;    //
    case SIGRTMIN4  = 38;    //
    case SIGRTMIN5  = 39;    //
    case SIGRTMIN6  = 40;    //
    case SIGRTMIN7  = 41;    //
    case SIGRTMIN8  = 42;    //
    case SIGRTMIN9  = 43;    //
    case SIGRTMIN10 = 44;    //
    case SIGRTMIN11 = 45;    //
    case SIGRTMIN12 = 46;    //
    case SIGRTMIN13 = 47;    //
    case SIGRTMIN14 = 48;    //
    case SIGRTMIN15 = 49;    //
    case SIGRTMAX14 = 50;    //
    case SIGRTMAX13 = 51;    //
    case SIGRTMAX12 = 52;    //
    case SIGRTMAX11 = 53;    //
    case SIGRTMAX10 = 54;    //
    case SIGRTMAX9  = 55;    //
    case SIGRTMAX8  = 56;    //
    case SIGRTMAX7  = 57;    //
    case SIGRTMAX6  = 58;    //
    case SIGRTMAX5  = 59;    //
    case SIGRTMAX4  = 60;    //
    case SIGRTMAX3  = 61;    //
    case SIGRTMAX2  = 62;    //
    case SIGRTMAX1  = 63;    //
    case SIGRTMAX   = 64;    //
}
