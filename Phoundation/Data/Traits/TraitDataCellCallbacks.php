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
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Stringable;


trait TraitDataCellCallbacks
{
    /**
     * Callback functions that, if specified, will be executed for each row in the list
     *
     * @var array $cell_callbacks
     */
    protected array $cell_callbacks = [];


    /**
     * Returns the source
     *
     * @return array
     */
    public function getCellCallbacks(): array
    {
        return $this->cell_callbacks;
    }


    /**
     * Set all callbacks to use
     *
     * @param array $cell_callbacks
     *
     * @return static
     */
    public function setCellCallbacks(array $cell_callbacks): static
    {
        foreach ($cell_callbacks as $callback) {
            $this->addCallback($callback);
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
    public function addCellCallback(callable $callbacks): static
    {
        $this->cell_callbacks[] = $callbacks;

        return $this;
    }


    /**
     * Clears the callbacks
     *
     * @return static
     */
    public function clearCellCallbacks(): static
    {
        $this->cell_callbacks = [];

        return $this;
    }


    /**
     * Execute the specified callbacks for each cell
     *
     * @note $params does NOT have a datatype specified as that would cause a crash when sending a non initialized
     *       variable there that would be assigned within this function
     *
     * @param string|float|int|null                 $row_id
     * @param string|float|int|null                 $column
     * @param Stringable|string|float|int|bool|null $value
     * @param IteratorInterface|array               $row
     * @param array                                 $params
     *
     * @return static
     */
    protected function executeCellCallbacks(string|float|int|null $row_id, string|float|int|null $column, Stringable|string|float|int|bool|null &$value, IteratorInterface|array &$row, array &$params): static
    {
        Arrays::ensure($params);
        Arrays::ensure($params['skiphtmlentities']);

        $params['htmlentities']           = $this->process_entities;
        $params['skiphtmlentities']['id'] = true;

        foreach ($this->cell_callbacks as $callback) {
            $callback($row_id, $column, $value, $row, $params);
        }

        return $this;
    }
}
