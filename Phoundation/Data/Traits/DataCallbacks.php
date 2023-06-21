<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataCallbacks
 *
 * Manages a callback functions registry that, if specified, will be executed for each row in a list
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataCallbacks
{
    /**
     * Callback functions that, if specified, will be executed for each row in the list
     *
     * @var array $callbacks
     */
    protected array $callbacks = [];


    /**
     * Returns the source
     *
     * @return array
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }


    /**
     * Set all callbacks to use
     *
     * @param array $callbacks
     * @return static
     */
    public function setCallbacks(array $callbacks): static
    {
        foreach ($callbacks as $callback) {
            $this->addCallback($callback);
        }

        return $this;
    }


    /**
     * Clears the callbacks
     *
     * @return static
     */
    public function clearCallbacks(): static
    {
        $this->callbacks = [];
        return $this;
    }


    /**
     * Adds a callback
     *
     * @param callable $callbacks
     * @return static
     */
    public function addCallback(callable $callbacks): static
    {
        $this->callbacks[] = $callbacks;
        return $this;
    }


    /**
     * Execute the specified callbacks for each row
     *
     * @param array $row
     * @return $this
     */
    public function executeCallbacks(array &$row): static
    {
        foreach ($this->callbacks as $callback) {
            $callback($row);
        }

        return $this;
    }
}