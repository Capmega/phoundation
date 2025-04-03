<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Phoundation\Data\DataEntries\Exception\DataEntryDisabledException;
use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Utils\Strings;
use Stringable;


interface RenderInterface extends Stringable
{
    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  Element::render(), ElementsBlock::render()
     */
    public function render(): ?string;

    /**
     * Returns true if the object has been rendered (and Object::render() will return cached render data), false
     * otherwise
     *
     * @return bool
     */
    public function hasRendered(): bool;

    /**
     * Throws an exception for the given action if the object is readonly
     *
     * @param string $action
     *
     * @return static
     * @throws DataEntryReadonlyException
     */
    public function checkReadonly(string $action): static;

    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public function isReadonly(): bool;

    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public function getReadonly(): bool;

    /**
     * Sets if this object is readonly or not
     *
     * @param bool $readonly
     *
     * @return static
     */
    public function setReadonly(bool $readonly): static;

    /**
     * Throws an exception for the given action if the object is disabled
     *
     * @param string $action
     *
     * @return static
     * @throws DataEntryDisabledException
     */
    public function checkDisabled(string $action): static;

    /**
     * Returns if this object is disabled or not
     *
     * @return bool
     */
    public function getDisabled(): bool;

    /**
     * Returns if this object is disabled or not
     *
     * @return bool
     */
    public function isDisabled(): bool;

    /**
     * Sets if this object is disabled or not
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setDisabled(bool $disabled): static;
}
