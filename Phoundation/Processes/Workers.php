<?php

namespace Phoundation\Processes;

use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Exception\WorkersException;
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
     * @var int $minimum
     */
    protected int $minimum = 0;

    /**
     * Maximum amount of workers required
     *
     * @var int $maximum
     */
    protected int $maximum = 10;

    /**
     * Amount of time in seconds that the process cycle should sleep before restarting
     *
     * @var int $cycle_sleep
     */
    protected int $cycle_sleep = 1;

    /**
     * The variable key that will be processed
     *
     * @var string|null $key
     */
    protected ?string $key = null;

    /**
     * The variable value list that this workers manager class must process
     *
     * @var array|null $values
     */
    protected ?array $values = null;



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
        return new static($command, $server);
    }



    /**
     * Returns the minimum amount of workers required
     *
     * @return int
     */
    public function getMinimum(): int
    {
        return $this->minimum;
    }



    /**
     * Sets the minimum amount of workers required
     *
     * @param int $minimum
     * @return static
     */
    public function setMinimum(int $minimum): static
    {
        $this->minimum = $minimum;
        return $this;
    }



    /**
     * Returns the maximum amount of workers required
     *
     * @return int
     */
    public function getMaximum(): int
    {
        return $this->maximum;
    }


    /**
     * Sets the maximum amount of workers required
     *
     * @param int $maximum
     * @return static
     */
    public function setMaximum(int $maximum): static
    {
        $this->maximum = $maximum;
        return $this;
    }



    /**
     * Returns the variable values list that this master worker will process
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }



    /**
     * Sets the variable values list that this master worker will process
     *
     * @param string $key
     * @return static
     */
    public function setKey(string $key): static
    {
        if (!preg_match('/^\$.+?\$$/', $key)) {
            throw new OutOfBoundsException(tr('Specified key ":key" is invalid, it should be in the form of "$keyname$"', [':key' => $key]));
        }

        $this->key = $key;
        return $this;
    }



    /**
     * Returns the variable values list that this master worker will process
     *
     * @return array|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }



    /**
     * Sets the variable values list that this master worker will process
     *
     * @param array $values
     * @return static
     */
    public function setValues(array $values): static
    {
        // Valiate values
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                throw new OutOfBoundsException(tr('Specified value ":value" from the values list is invalid, it should be scalar', [':value' => $value]));
            }
        }

        $this->values = $values;
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
        // We need BOTH key and values OR NONE
        if (!$this->key and $this->values) {
            throw new WorkersException(tr('Values specified without key'));
        }

        if ($this->key and !$this->values) {
            throw new WorkersException(tr('Key specified without values'));
        }

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
        $value = array_shift($this->values);

        $worker = clone $this;
        $worker
            ->setVariables([$this->key => $value])
            ->executeBackground();

        $this->workers[$worker->getPid()] = $worker;

        Log::success(tr('Started worker with PID ":pid" for value ":value"', [':pid' => $worker->getPid(), ':value' => $value]));
    }
}