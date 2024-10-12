<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Stringable;

interface DefinitionsInterface extends IteratorInterface
{
    /**
     * Returns the column prefix
     *
     * @return string|null
     */
    public function getColumnPrefix(): ?string;


    /**
     * Sets the column prefix
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setColumnPrefix(?string $prefix): static;


    /**
     * Returns the data entry
     *
     * @return DataEntryInterface|null
     */
    public function getDataEntry(): ?DataEntryInterface;


    /**
     * Sets the data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public function setDataEntry(DataEntryInterface $data_entry): static;


    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface
     */
    public function current(): DefinitionInterface;


    /**
     * Returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return DefinitionInterface|null
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): ?DefinitionInterface;


    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface
     */
    public function getFirstValue(): DefinitionInterface;


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface
     */
    public function getLastValue(): DefinitionInterface;


    /**
     * Direct method to hide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function hide(Stringable|string|float|int $key, bool $exception = true): static;


    /**
     * Direct method to unhide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function show(Stringable|string|float|int $key, bool $exception = true): static;


    /**
     * Returns if meta-information is visible at all, or not
     *
     * @return bool
     */
    public function getMetaVisible(): bool;

    /**
     * Returns if any buttons have been defined
     *
     * @return bool
     */
    public function hasButtons(): bool;

    /**
     * Returns the modal buttons
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface;

    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $buttons
     *
     * @return static
     */
    public function setButtons(?ButtonsInterface $buttons): static;

    /**
     * Adds the specified buttons to this buttons list
     *
     * @param ButtonsInterface|null $buttons
     *
     * @return static
     */
    public function addButtons(?ButtonsInterface $buttons): static;

    /**
     * Sets the modal buttons
     *
     * @param ButtonInterface|null $button
     *
     * @return static
     */
    public function addButton(?ButtonInterface $button): static;

    /**
     * Direct method to render or not render entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setRender(Stringable|string|float|int $key, bool $render, bool $exception = true): static;

    /**
     * Direct method to set size for entries
     *
     * @param Stringable|string|float|int $key
     * @param int                         $size
     * @param bool                        $exception
     *
     * @return static
     */
    public function setSize(Stringable|string|float|int $key, int $size, bool $exception = true): static;
}
