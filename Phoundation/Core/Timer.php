<?php

declare(strict_types=1);

namespace Phoundation\Core;

use Phoundation\Core\Exception\TimerException;
use Phoundation\Core\Interfaces\TimerInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Timer
 *
 * This is a standard timer object to measure passed time using PHP microtime()
 *
 * Once a timer is created it is automatically added to the Timers class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 * @see Timers
 */
class Timer implements TimerInterface
{
    /**
     * Record the moment when the timer is started
     *
     * @var float|null $start
     */
    protected ?float $start = null;

    /**
     * Record the moment when the timer is stopped
     *
     * @var float|null $stop
     */
    protected ?float $stop = null;

    /**
     * The last recorded timestamp
     *
     * @var float|null $last
     */
    protected ?float $last = null;

    /**
     * Record lap
     *
     * @var array $laps
     */
    protected array $laps = [];

    /**
     * Timer label
     *
     * @var null|string $label
     */
    protected ?string $label = null;


    /**
     * Timer constructor
     *
     * @param string $label
     * @param bool $start
     */
    protected function __construct(string $label = '', bool $start = true)
    {
        $this->label = get_null($label) ?? '-';

        if ($start) {
            $this->start();
        }
    }


    /**
     * Returns a new Timer object
     *
     * @param string $label
     * @param bool $start
     * @return TimerInterface
     */
    public static function new(string $label = '', bool $start = true): TimerInterface
    {
        return new static($label, $start);
    }


    /**
     * Returns the sub key for this timer
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }


    /**
     * Returns the start time for this timer
     *
     * @return float|null
     */
    public function getStart(): ?float
    {
        return $this->start;
    }


    /**
     * Returns the stop time for this timer
     *
     * @return float|null
     */
    public function getStop(): ?float
    {
        return $this->stop;
    }


    /**
     * Returns the passed time for this timer
     *
     * @return float
     */
    public function getPassed(): float
    {
        static::checkTimer('start', false,'get passed time');
        static::checkTimer('stop' , true ,'get passed time');

        return microtime(true) - $this->start;
    }


    /**
     * Returns the passed time for this timer
     *
     * @return float
     */
    public function getTotal(): float
    {
        static::checkTimer('start', false,'get total time');
        static::checkTimer('stop' , false,'get total time');

        return $this->stop - $this->start;
    }


    /**
     * Returns all the passed laps for this timer
     *
     * @return array
     */
    public function getLaps(): array
    {
        static::checkTimer('start', false, 'get laps');
        static::checkTimer('stop' , false, 'get laps');
        return $this->laps;
    }


    /**
     * Starts the timer
     *
     * @return static
     */
    public function start(): static
    {
        static::checkTimer('start', true, 'start timer');

        $this->start = microtime(true);
        $this->last  = $this->start;

        return $this;
    }


    /**
     * Records a passed lap and returns the time for that lap
     *
     * @return static
     */
    public function lap(): static
    {
        static::checkTimer('start', false, 'lap timer');
        static::checkTimer('stop' , true , 'lap timer');

        $time         = microtime(true);
        $this->laps[] = $time - $this->last;
        $this->last   = $time;

        return $this;
    }


    /**
     * Stop the specified stopwatch and returns the passed time
     *
     * @param bool $force
     * @return static
     */
    public function stop(bool $force = false): static
    {
        if ($force and $this->stop) {
            // Timer was already stopped, ignore, this was just stopped "to be sure", usually by Core::exit()
            return $this;
        }

        static::checkTimer('start', false, 'stop timer');
        static::checkTimer('stop' , true , 'stop timer');

        // Get the passed time for this lap and calculate the passed time
        $this->stop   = microtime(true);
        $this->laps[] = $this->stop - $this->last;
        $this->last   = $this->stop;

        return $this;
    }


    /**
     * Check if timer registration matches requirements
     *
     * @param string $status
     * @param bool $null
     * @param string $message
     * @return void
     */
    protected function checkTimer(string $status, bool $null, string $message): void
    {
        if ($null xor ($this->$status === null)) {
            $status = match ($status) {
                'start' => tr('started'),
                'stop'  => tr('stopped'),
                default =>
                    throw new OutOfBoundsException(tr('Unknown status ":status" specified, only "start" and "stop" are allowed', [
                        ':status' => $status
                    ]))
            };

            throw new TimerException(tr('Cannot :message for timer ":label", it has not yet :status', [
                ':status'  => $status,
                ':message' => $message,
                ':label'   => $this->label
            ]));
        }
    }
}
