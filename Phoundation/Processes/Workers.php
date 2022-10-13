<?php

namespace Phoundation\Processes;



use Phoundation\Cli\Cli;
use Phoundation\Core\Log;
use Phoundation\Servers\Server;

/**
 * Class Workers
 *
 * This class can manage worker processes running in the background
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 * @uses \Phoundation\Processes\ProcessVariables
 */
class Workers extends Process
{
    /**
     * The workers that are managed by this class
     *
     * @var array
     */
    protected array $workers = [];

    /**
     * Minimum amount of workers required
     *
     * @var int|null $minimum
     */
    protected ?int $minimum = null;

    /**
     * Maximum amount of workers required
     *
     * @var int|null $maximum
     */
    protected ?int $maximum = null;

    /**
     * Amount of time in seconds that the process cycle should sleep before restarting
     *
     * @var int $cycle_sleep
     */
    protected int $cycle_sleep = 1;



    /**
     * Workers constructor
     *
     * @param string|null $command
     * @param Server|null $server
     */
    public function __construct(?string $command = null, ?Server $server = null)
    {
        $this->setCommand($command);
        $this->setServer($server);
    }



    /**
     * Returns a new Workers object
     *
     * @param string|null $command
     * @param Server|null $server
     * @return static
     */
    public static function create(?string $command = null, ?Server $server = null): static
    {
        return new static();
    }



    /**
     * Returns the minimum amount of workers required
     *
     * @return int|null
     */
    public function getMinimum(): ?int
    {
        return $this->minimum;
    }



    /**
     * Sets the minimum amount of workers required
     *
     * @param int|null $minimum
     * @return static
     */
    public function setMinimum(?int $minimum): static
    {
        $this->minimum = $minimum;
        return $this;
    }



    /**
     * Returns the maximum amount of workers required
     *
     * @return int|null
     */
    public function getMaximum(): ?int
    {
        return $this->maximum;
    }



    /**
     * Sets the maximum amount of workers required
     *
     * @param int|null $maximum
     * @return static
     */
    public function setMaximum(?int $maximum): static
    {
        $this->maximum = $maximum;
        return $this;
    }



    /**
     * Returns the current amount of workers running
     *
     * @return int
     */
    public function getCurrent(): int
    {
        return count($this->workers);
    }



    /**
     * Start running the workers as background processes
     *
     * @return void
     */
    public function start(): void
    {
        $current = 0;

        while(true) {
            if ($current < $this->maximum) {
                $this->startWorker();

            } else {
                Log::notice(tr('Current amount of workers ":current" is higher than the maximum of ":max", not starting new workers', [':current' => $current, ':max' => $this->maximum]));
            }

            sleep($this->cycle_sleep);
            $current = $this->getCurrent();
        }
    }



    /**
     * Stop all background running workers
     *
     * @return void
     */
    public function stop(): void
    {
        foreach ($this->workers as $worker) {
            $worker->kill();
        }
    }



    /**
     * Start a background worker
     *
     * @return void
     */
    protected function startWorker(): void
    {
        $worker = clone $this;
        $worker->executeBackground();
        $this->workers[$worker->getPid()] = $worker;
    }
}