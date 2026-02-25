<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Definitions\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\DropdownButtonInterface;
use ReturnTypeWillChange;
use Stringable;

interface DefinitionsInterface extends IteratorInterface
{
    /**
     * Returns the column prefix
     *
     * @return string|null
     */
    public function getPrefix(): ?string;


    /**
     * Sets the column prefix
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setPrefix(?string $prefix): static;


    /**
     * Returns the data entry
     *
     * @return DataEntryInterface|null
     */
    public function getDataEntryObject(): ?DataEntryInterface;


    /**
     * Sets the data entry
     *
     * @param DataEntryInterface $_data_entry
     *
     * @return static
     */
    public function setDataEntryObject(DataEntryInterface $_data_entry): static;


    /**
     * Returns the current Definition object
     *
     * @return DefinitionInterface|null
     */
    public function current(): ?DefinitionInterface;

    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?DefinitionInterface;

    /**
     *
     */
    #[ReturnTypeWillChange] public function getRandom(): ?DefinitionInterface;

    /**
     * Returns the first Definition entry
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?DefinitionInterface;


    /**
     * Returns the last Definition entry
     *
     * @return DefinitionInterface|null
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?DefinitionInterface;


    /**
     * Direct method to hide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function hideDefinition(Stringable|string|float|int $key, bool $exception = true): static;


    /**
     * Direct method to unhide entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return static
     */
    public function showDefinition(Stringable|string|float|int $key, bool $exception = true): static;


    /**
     * Returns if meta-information is visible at all, or not
     *
     * @return bool
     */
    public function getRenderMeta(): bool;

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
    public function getButtonsObject(): ButtonsInterface;

    /**
     * Sets the modal buttons
     *
     * @param ButtonsInterface|null $buttons
     *
     * @return static
     */
    public function setButtonsObject(?ButtonsInterface $buttons): static;

    /**
     * Adds the specified buttons to this buttons list
     *
     * @param ButtonsInterface|null $_buttons
     *
     * @return static
     */
    public function addButtons(?ButtonsInterface $_buttons): static;

    /**
     * Sets the modal buttons
     *
     * @param DropdownButtonInterface|ButtonInterface|null $_button
     *
     * @return static
     */
    public function addButton(DropdownButtonInterface|ButtonInterface|null $_button): static;

    /**
     * Direct method to render or not render entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionRender(Stringable|string|float|int $key, bool $render, bool $exception = true): static;

    /**
     * Direct method to set size for entries
     *
     * @param Stringable|string|float|int $key
     * @param int                         $size
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionSize(Stringable|string|float|int $key, int $size, bool $exception = true): static;

    /**
     * Sets if meta-information is visible at all, or not
     *
     * @param bool $render_meta
     *
     * @return static
     */
    public function setRenderMeta(bool $render_meta): static;

    /**
     * Modify the specified definition directly
     *
     * @param Stringable|string|float|int $key
     * @param array $key_values
     * @param bool $exception
     *
     * @return static
     */
    public function modifyDefinition(Stringable|string|float|int $key, array $key_values, bool $exception = true): static;

    /**
     * Direct method to return weather the specified column renders or not
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return bool
     */
    public function isRendered(Stringable|string|float|int $key, bool $exception = true): bool;

    /**
     * Removes the definitions column prefix from the specified key and returns it
     *
     * @param string $key
     *
     * @return string
     */
    public function removeColumnPrefix(string $key): string;

    /**
     * Direct method to render or not display entries
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $render
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionDisplay(Stringable|string|float|int $key, bool $render, bool $exception = true): static;

    /**
     * Direct method to make entries readonly
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $readonly
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionReadonly(Stringable|string|float|int $key, bool $readonly, bool $exception = true): static;

    /**
     * Direct method to make entries disabled
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $disabled
     * @param bool                        $exception
     *
     * @return static
     */
    public function setDefinitionDisabled(Stringable|string|float|int $key, bool $disabled, bool $exception = true): static;

    /**
     * Returns the specified column
     *
     * @param Stringable|string|float|int $key
     * @param Stringable|string|float|int $target
     * @param bool                        $exception
     *
     * @return DefinitionInterface|null
     */
    public function renameKey(Stringable|string|float|int $key, Stringable|string|float|int $target, bool $exception = true): ?DefinitionInterface;
}
