<?php

/**
 * Class ServiceCore
 *
 *
 *
 * @see https://stackoverflow.com/questions/2036654/run-php-script-as-daemon-process/44420339#44420339
 * @see https://www.amazon.com/dp/0321525949
 * @see https://www.php.net/manual/en/function.umask.php#91569
 * @see https://www.php.net/pcntl_fork
 * @see Core::fork()
 * @see https://www.php.net/manual/en/function.posix-setsid.php
 * @see https://www.rabbitmq.com/
 * @see https://github.com/jakubkulhan/bunny
 * @see https://amphp.org/
 * @see https://github.com/spatie/fork
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services;

use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataOsProcessName;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Services\Exception\ServiceAlreadyRunningException;
use Phoundation\Os\Services\Exception\ServiceException;
use Phoundation\Os\Services\Exception\ServiceSessionException;


class ServiceCore
{
    use TraitDataOsProcessName {
        setOsProcessName as protected __setOsProcessName;
    }


    /**
     * Tracks the POSIX process session id
     *
     * @var int|null $session_id
     */
    protected ?int $session_id = null;

    /**
     * Tracks the chance that garbage collection will execute in a service cycle
     *
     * @var int $cycle_gc_chance
     */
    protected int $cycle_gc_chance = 5;

    /**
     * Tracks the time in microseconds that each cycle will sleep
     *
     * @var int $cycle_sleep
     */
    protected int $cycle_sleep = 5;


    /**
     * ServiceCore class constructor
     */
    public function __construct()
    {
        if (!PLATFORM_CLI) {
            throw new ServiceException(tr('Only CLI commands can be executed as a service'));
        }

        $this->setCycleGcChance(5)
             ->setCycleSleep(5000)
             ->detectOsProcessName();
    }


    /**
     * Returns a CLI autocomplete configuration
     *
     * @param array $merge
     * @return array
     */
    public static function getAutoComplete(array $merge): array
    {
        return array_replace($merge, [
            'positions' => [
                0 => Service::getCommandsList(),
            ]
        ]);
    }


    /**
     * Returns a list of available commands
     *
     * @return array
     */
    public static function getCommandsList(): array
    {
        return ['start', 'stop', 'restart', 'status', 'show'];
    }


    /**
     * Returns the chance in % that garbage collection will execute forcibly in a service cycle
     *
     * Defaults to 5
     *
     * @return int
     */
    public function getCycleGcChance(): int
    {
        return $this->cycle_gc_chance;
    }


    /**
     * Sets the chance in % that garbage collection will execute forcibly in a service cycle
     *
     * Defaults to 5
     *
     * @note A value of 0 will disable forced garbage collection
     *
     * @param int $chance
     * @return static
     */
    public function setCycleGcChance(int $chance): static
    {
        if (($chance < 0) or ($chance > 100)) {
            throw new OutOfBoundsException(tr('Invalid cycle garbage collector chance value ":chance" specified, must be integer between 0 (disabled) and 100', [
                ':sleep' => $chance
            ]));
        }

        $this->cycle_gc_chance = $chance;
        return $this;
    }


    /**
     * Returns the time in microseconds that each cycle will sleep
     *
     * Defaults to 5000
     *
     * @return int
     */
    public function getCycleSleep(): int
    {
        return $this->cycle_sleep;
    }


    /**
     * Sets the time in microseconds that each cycle will sleep
     *
     * Defaults to 5000
     *
     * @param int $sleep
     * @return static
     */
    public function setCycleSleep(int $sleep): static
    {
        if (($sleep < 0) or ($sleep > 84_600_000_0000)) {
            throw new OutOfBoundsException(tr('Invalid cycle sleep ":sleep" specified, must be integer between 0 and 84_600_000_0000', [
                ':sleep' => $sleep
            ]));
        }

        $this->cycle_sleep = $sleep;
        return $this;
    }


    /**
     * Starts the service cycle (specified as a callback) and performs automatic garbage collection
     *
     * @param callable $cycle
     * @return static
     */
    public function execute(callable $cycle): static
    {
        while (true) {
            $cycle();

            if ($this->cycle_sleep) {
                usleep($this->cycle_sleep);
            }

            if (mt_rand(1, 100) <= $this->cycle_gc_chance) {
                gc_collect_cycles();
            }
        }
    }


    /**
     * Returns true if this process is currently running as a service
     *
     * @return bool
     */
    public function isRunning(): bool
    {

    }


    /**
     * Returns true if this process is already a service
     *
     * @return bool
     */
    public static function processIsService(): bool
    {
        return posix_getppid() === 1;
    }


    /**
     * Forks this process as a background process
     *
     * @return static
     */
    public function start(): static
    {
        if ($this->processIsService()) {
            throw new ServiceAlreadyRunningException(tr('Cannot fork process ":process" as a service, it is already running', [
                ':process' => CliCommand::getCommandsString()
            ]));
        }

        umask(0000);

        Core::fork(
            function() {
                Log::success(tr('Started service ":command"', [
                    ':command' => CliCommand::getCommandsString()
                ]));

                exit();
            },

            function(int $pid) {
                $this->session_id = posix_setsid();

                if ($this->session_id < 0) {
                    throw new ServiceSessionException(tr('Failed to make current process ":pid" a session leader', [
                        ':pid' => $pid
                    ]));
                }
            }
        );

        return $this;
    }


    /**
     * Stops the current service
     *
     * @return static
     */
    public function stop(): static
    {

    }
}
