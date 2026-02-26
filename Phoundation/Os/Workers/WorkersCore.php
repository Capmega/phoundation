<?php

/**
 * Class WorkersCore
 *
 * This class can manage worker processes running in the background
 *
 * .....
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      TraitProcessVariables
 */


declare(strict_types=1);

namespace Phoundation\Os\Workers;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Ps;
use Phoundation\Os\Processes\Exception\WorkersException;
use Phoundation\Os\Processes\Interfaces\WorkersCoreInterface;
use Phoundation\Os\Processes\ProcessCore;
use Phoundation\Os\Traits\TraitDataFloatIntMaximumExecutionTime;
use Phoundation\Utils\Strings;


class WorkersCore extends ProcessCore implements WorkersCoreInterface
{
    use TraitDataLabel;
    use TraitDataFloatIntMaximumExecutionTime;


    /**
     * The workers that are managed by this class
     *
     * @var array
     */
    protected array $workers = [];

    /**
     * Minimum number of workers required
     *
     * @var int $minimum
     */
    protected int $minimum = 0;

    /**
     * Maximum number of workers required
     *
     * @var int $maximum
     */
    protected int $maximum = 10;

    /**
     * Amount of time in microseconds that the process cycle should sleep before retrying to start workers
     *
     * @var int $cycle_sleep
     */
    protected int $cycle_sleep = 200_000;

    /**
     * Amount of time in microseconds for each process wait cycle that it waits for workers to finish
     *
     * @var int $wait_sleep
     */
    protected int $wait_sleep = 1_000_000;

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
     * The number of cycles that must be executed
     *
     * @var int|null $cycles
     */
    protected ?int $cycles = 0;

    /**
     * Counter for the number of workers that have been executed
     *
     * @var int
     */
    protected int $workers_executed = 0;

    /**
     * If true, this process will wait for the workers to finish before returning
     *
     * @var bool $wait_worker_finish
     */
    protected bool $wait_worker_finish = false;


    /**
     * Workers constructor
     *
     * @param PhoRestrictionsInterface|PhoDirectoryInterface|null $_execution_directory
     */
    public function __construct(PhoRestrictionsInterface|PhoDirectoryInterface|null $_execution_directory = null)
    {
        parent::__construct($_execution_directory);
    }


    /**
     * Returns if this process waits for the workers to finish before returning
     *
     * @return bool
     */
    public function getWaitWorkerFinish(): bool
    {
        return $this->wait_worker_finish;
    }


    /**
     * Sets if this process waits for the workers to finish before returning
     *
     * @param bool $wait_worker_finish
     *
     * @return static
     */
    public function setWaitWorkerFinish(bool $wait_worker_finish): static
    {
        $this->wait_worker_finish = $wait_worker_finish;
        return $this;
    }


    /**
     * Returns the minimum number of workers required
     *
     * @return int
     */
    public function getMinimumWorkers(): int
    {
        return $this->minimum;
    }


    /**
     * Sets the minimum number of workers required
     *
     * @param int|null $minimum
     *
     * @return static
     */
    public function setMinimumWorkers(?int $minimum): static
    {
        $this->minimum = $minimum;
        return $this;
    }


    /**
     * Returns the maximum number of workers required
     *
     * @return int
     */
    public function getMaximumWorkers(): int
    {
        return $this->maximum;
    }


    /**
     * Sets the maximum number of workers required
     *
     * @param int $maximum
     *
     * @return static
     */
    public function setMaximumWorkers(int $maximum): static
    {
        $this->maximum = $maximum;
        return $this;
    }


    /**
     * Returns amount of time in milliseconds that the process cycle should sleep before retrying to start workers
     *
     * @return int
     */
    public function getWaitSleep(): int
    {
        return $this->wait_sleep;
    }


    /**
     * Sets amount of time in milliseconds that the process cycle should sleep before retrying to start workers
     *
     * @param int $wait_sleep
     *
     * @return static
     */
    public function setWaitSleep(int $wait_sleep): static
    {
        $this->wait_sleep = $wait_sleep;
        return $this;
    }


    /**
     * Returns amount of time in milliseconds that the process cycle should sleep each cycle while checking alive
     * workers
     *
     * @return int
     */
    public function getCycleSleep(): int
    {
        return $this->cycle_sleep;
    }


    /**
     * Sets number of time in milliseconds that the process cycle should sleep each cycle while checking alive workers
     *
     * @param int $cycle_sleep
     *
     * @return static
     */
    public function setCycleSleep(int $cycle_sleep): static
    {
        $this->cycle_sleep = $cycle_sleep;
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
     *
     * @return static
     */
    public function setKey(string $key): static
    {
        if (!preg_match('/^:[A-Z0-9]+[A-Z0-9-]*[A-Z0-9]+$/', $key)) {
            throw new OutOfBoundsException(tr('Specified key ":key" is invalid, it should match pattern "/^:[A-Z0-9]+[A-Z0-9-]*[A-Z0-9]+$/", so a : symbol, and then at least 2 characters that can be only uppercase letters, or numbers, or dash, and cannot begin or end with a dash', [
                ':key' => $key,
            ]));
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
     *
     * @return static
     */
    public function setValues(array $values): static
    {
        if ($this->values !== null) {
            throw new OutOfBoundsException(tr('Cannot set both values list and cycles'));
        }

        // Validate values
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                throw new OutOfBoundsException(tr('Specified value ":value" from the values list is invalid, it should be scalar', [
                    ':value' => $value,
                ]));
            }
        }

        $this->values = $values;
        return $this;
    }


    /**
     * Returns the number of cycles that should be executed if the workers should not process a list
     *
     * @return int|null
     */
    public function getCycles(): ?int
    {
        return $this->cycles;
    }


    /**
     * Sets the number of cycles that should be executed if the workers should not process a list
     *
     * @param int|null $cycles
     *
     * @return static
     */
    public function setCycles(?int $cycles): static
    {
        if ($this->values !== null) {
            throw new OutOfBoundsException(tr('Cannot set both cycles and values list'));
        }

        $this->cycles = $cycles;
        return $this;
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

        if (!$this->values) {
            if (!$this->cycles) {
                throw new OutOfBoundsException(tr('Cannot run workers, no cycles nor value list specified'));
            }

            $this->values = range(1, $this->cycles);
        }

        while (true) {
            if (!$this->values) {
                Log::success(ts('Finished processing values list with ":count" workers', [
                    ':count' => $this->workers_executed
                ]));
                break;
            }

            if ($current < $this->maximum) {
                $this->startWorker();

                usleep($this->cycle_sleep);
                $current = $this->getCurrent();

                Log::notice(ts('Processing list with ":count" workers', [
                    ':count' => $current
                ]), 6);

            } else {
                Log::warning(ts('Current number of workers ":current" is higher than the maximum of ":max", not starting new workers', [
                    ':current' => $current,
                    ':max'     => $this->maximum,
                ]), 3);

                usleep($this->cycle_sleep);
                $current = $this->getCurrent();
            }

            Core::exitOverRuntime($this->getMaximumExecutionTime());
        }

        if ($this->wait_worker_finish) {
            while (true) {
                $current = $this->getCurrent();
                Log::notice(ts('Waiting for ":count" workers to finish', [':count' => $current]));

                if (!$this->getCurrent()) {
                    Log::success(ts('All workers finished'));
                    break;
                }

                msleep($this->wait_sleep);
            }
        }
    }


    /**
     * Start a background worker
     *
     * @return void
     */
    protected function startWorker(): void
    {
        $value  = array_shift($this->values);
        $worker = clone $this;
        $worker->setVariables([$this->key => $value]);

        Log::action(ts('Starting worker with command ":command"', [
            ':command' => $worker->getFullCommandLine(),
        ]), 3);

        $worker->executeBackground();
        $this->workers[$worker->getPid()] = $worker;
        $this->workers_executed++;

        Log::success(ts('Started worker with PID ":pid" for ":label" ":value"', [
            ':pid'   => $worker->getPid(),
            ':label' => not_empty($this->label, tr('value')),
            ':value' => $value,
        ]), 4);
    }


    /**
     * Returns the current number of workers running
     *
     * @return int
     */
    public function getCurrent(): int
    {
        $this->cleanWorkers();

        return count($this->workers);
    }


    /**
     * Clean gone workers from the workers list
     *
     * @return void
     */
    protected function cleanWorkers(): void
    {
        // Check the workers that are still active
        foreach ($this->workers as $pid => $worker) {
            $ps = Ps::new($this->_restrictions)
                    ->ps($pid);

            if ($ps) {
                // There is A process, but is it the right one? Cleanup both commands to compare
                $args    = trim(Strings::from(Strings::untilReverse($worker->getFullCommandLine(), ';'), 'set -o'));
                $ps_args = trim(Strings::from(Strings::untilReverse($ps['args'], ';'), 'set -o'));

                if ($ps_args === $args) {
                    // Yep, this worker is still active
                    continue;
                }
            }

            // This worker is dead, remove it from the list
            Log::notice(ts('Worker with PI ":pid" finished process, removing from list', [':pid' => $pid]));
            unset($this->workers[$pid]);
        }
    }


    /**
     * Stop all background-running workers
     *
     * @return void
     */
    public function stop(): void
    {
        foreach ($this->workers as $worker) {
            $worker->kill();
        }
    }
}
