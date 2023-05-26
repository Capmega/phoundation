<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Interfaces;
use Phoundation\Web\Http\Html\Components\Form;
use Stringable;


/**
 * Interface ElementsBlock
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface InterfaceElementsBlock extends Stringable
{
    /**
     * Returns the contents of this object as an array
     *
     * @return array
     */
    public function __toArray(): array;

    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     * @return static
     */
    public function useForm(bool $use_form): static;

    /**
     * Returns the form of this objects block
     *
     * @return Form|null
     */
    public function getForm(): ?Form;

    /**
     * Returns the form of this objects block
     *
     * @param Form|null $form
     * @return static
     */
    public function setForm(?Form $form): static;

    /**
     * Returns the source of this object
     *
     * @return array|null
     */
    public function getSource(): ?array;

    /**
     * Returns the specified entry from the source of this object
     *
     * @param string|int $entry
     * @return mixed
     */
    public function getSourceEntry(string|int $entry): mixed;

    /**
     * Sets the data source of this object
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static;

    /**
     * Sets the data source of this object
     *
     * @param array|null $source
     * @return $this
     */
    public function addSource(?array $source): static;

    /**
     * Adds a single entry to the source of this object
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addSourceEntry(string $key, mixed $value): static;

    /**
     * Renders and returns the HTML for this object using the template renderer if avaialable
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see InterfaceElement::render()
     */
    public function render(): ?string;

    /**
     * Returns if this FlashMessages object has rendered HTML or not
     *
     * @return bool
     */
    public function hasRendered(): bool;

    /**
     * Clear all messages in this object
     *
     * @return $this
     */
    public function clear(): static;

    /**
     * Return the amount of flash messages in this object
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns the specified item
     *
     * @param string|int $key
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(string|int $key): mixed;

    /**
     * Returns if the specified key exists or not
     *
     * @param string|int $key
     * @return bool
     */
    public function exists(string|int $key): bool;

    /**
     * Returns the current item
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed;

    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static;

    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static;

    /**
     * Returns the current iterator position
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function key(): mixed;

    /**
     * Returns if the current element exists or not
     *
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewinds the internal pointer to 0
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static;
}