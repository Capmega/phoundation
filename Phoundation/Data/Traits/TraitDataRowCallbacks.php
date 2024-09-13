<?php

/**
 * Trait TraitDataCallbacks
 *
 * Manages a callback functions registry that, if specified, will be executed for each row in a list
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Enums\EnumTableRowType;


trait TraitDataRowCallbacks
{
    /**
     * Callback functions that, if specified, will be executed for each row in the list
     *
     * @var array $row_callbacks
     */
    protected array $row_callbacks = [];


    /**
     * Returns the source
     *
     * @return array
     */
    public function getRowCallbacks(): array
    {
        return $this->row_callbacks;
    }


    /**
     * Set all callbacks to use
     *
     * @param array $row_callbacks
     *
     * @return static
     */
    public function setRowCallbacks(array $row_callbacks): static
    {
        foreach ($row_callbacks as $callback) {
            $this->addRowCallback($callback);
        }

        return $this;
    }


    /**
     * Adds a callback
     *
     * @param callable $callbacks
     *
     * @return static
     */
    public function addRowCallback(callable $callbacks): static
    {
        $this->row_callbacks[] = $callbacks;

        return $this;
    }


    /**
     * Clears the callbacks
     *
     * @return static
     */
    public function clearRowCallbacks(): static
    {
        $this->row_callbacks = [];

        return $this;
    }


    /**
     * Execute the specified callbacks for each row
     *
     * @note $params does NOT have a datatype specified as that would cause a crash when sending a non initialized
     *       variable there that would be assigned within this function
     *
     * @param IteratorInterface|array $row
     * @param EnumTableRowType        $type
     * @param                         $params
     *
     * @return static
     */
    protected function executeRowCallbacks(IteratorInterface|array &$row, EnumTableRowType $type, &$params): static
    {
        $params = [
            'htmlentities'     => $this->process_entities,
            'skiphtmlentities' => ['id' => true],
        ];
        foreach ($this->row_callbacks as $callback) {
            $callback($row, $type, $params);
        }

        return $this;
    }
}
