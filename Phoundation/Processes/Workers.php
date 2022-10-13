<?php

namespace Phoundation\Processes;



use Phoundation\Cli\Cli;
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
class Workers
{
    use ProcessVariables;



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
        $pid = getmypid();
        $children = ProcessCommands::server($this->server)->getChildren($pid);

        return count($children);
    }



    /**
     * Start running the workers as background processes
     *
     * @return void
     */
    public function start(): void
    {

    }



    /**
     * Stop all background running workers
     *
     * @return void
     */
    public function stop(): void
    {

    }



    /**
     * Start a background worker
     *
     * @return void
     */
    protected function startWorker(): void
    {
        $worker = new Worker($this->server);
    }
}