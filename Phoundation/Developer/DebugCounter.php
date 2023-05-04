<?php

declare(strict_types=1);

namespace Phoundation\Developer;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Class DebugCounter
 *
 * This class contains a simple counter that can be used for debugging.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class DebugCounter
{
    /**
     * The counter store
     *
     * @var array $counters
     */
    protected array $counters = [];

    /**
     * The selected counter
     *
     * @var string|null $counter = null
     */
    protected ?string $counter = null;


    /**
     * Set which counter to use
     *
     * @param string $counter
     * @return DebugCounter
     */
    public function select(string $counter): DebugCounter
    {
        if (!$counter) {
            throw new OutOfBoundsException(tr('No counter specified'));
        }

        $this->counter = $counter;

        if (!array_key_exists($counter, $this->counters)) {
            $this->counters[$counter] = 0;
        }

        return $this;
    }


    /**
     * Increase the current counter by one
     *
     * @return void
     */
    public function increase(): void
    {
        $this->counters[$this->counter]++;
    }


    /**
     * Return a list of all counters
     *
     * @return array
     */
    public function listCounters(): array
    {
        return $this->counters;
    }
}