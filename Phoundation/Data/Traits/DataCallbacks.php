<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Enums\Interfaces\EnumTableRowTypeInterface;


/**
 * Trait DataCallbacks
 *
 * Manages a callback functions registry that, if specified, will be executed for each row in a list
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @note $params does NOT have a datatype specified as that would cause a crash when sending a non initialized
     *       variable there that would be assigned within this function
     * @param IteratorInterface|array $row
     * @param EnumTableRowTypeInterface $type
     * @param $params
     * @return $this
     */
    protected function executeCallbacks(IteratorInterface|array &$row, EnumTableRowTypeInterface $type, &$params): static
    {
        $params = [
            'htmlentities'     => $this->process_entities,
            'skiphtmlentities' => ['id' => true]
        ];

        foreach ($this->callbacks as $callback) {
            $callback($row, $type, $params);
        }

        return $this;
    }
}
