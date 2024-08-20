<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Html\Components\Forms\DataEntryFormColumn;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

interface DataEntryFormRowsInterface
{
    /**
     * Returns the maximum number of columns per row
     *
     * @return int
     */
    public function getColumnCount(): int;


    /**
     * Sets the maximum number of columns per row
     *
     * @param int $count
     *
     * @return static
     */
    public function setColumnCount(int $count): static;


    /**
     * Adds the column component and its definition as a DataEntryFormColumn
     *
     * @param DefinitionInterface|null    $definition
     * @param RenderInterface|string|null $component
     *
     * @return static
     */
    public function add(?DefinitionInterface $definition = null, RenderInterface|string|null $component = null): static;


    /**
     * Adds the specified DataEntryFormColumn to this DataEntryFormRow
     *
     * @param DataEntryFormColumn $column
     *
     * @return static
     */
    public function addColumn(DataEntryFormColumn $column): static;


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string;
}
