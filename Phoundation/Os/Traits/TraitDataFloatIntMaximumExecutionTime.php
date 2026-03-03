<?php

/**
 * Trait TraitDataAction
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Os\Traits;


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataFloatIntMaximumExecutionTime
{
    /**
     * Tracks the maximum time a worker master process may be running
     *
     * @var float|int|null $maximum_execution_time
     */
    protected float|int|null $maximum_execution_time = null;


    /**
     * Sets the maximum amount of time that a master worker process may be running
     *
     * @return float|int|null
     */
    public function getMaximumExecutionTime(): float|int|null
    {
        return $this->maximum_execution_time;
    }


    /**
     * Sets the maximum amount of time that a master worker process may be running
     *
     * @param float|int|null $time
     *
     * @return static
     */
    public function setMaximumExecutionTime(float|int|null $time): static
    {
        if ($time < 0) {
            throw OutOfBoundsException::new(ts('Invalid maximum execution time ":time" specified', [
                ':time' => $time
            ]));
        }

        $this->maximum_execution_time = $time;
        return $this;
    }
}
