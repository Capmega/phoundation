<?php

/**
 * Interface TemplateInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Templates
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages\Interfaces;

use Phoundation\Data\DataEntries\Exception\DataEntryDisabledException;
use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Interfaces\ComponentInterface;


interface TemplateInterface extends ComponentInterface
{
    /**
     * Returns the rendered version of this object
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Returns a new Template page object
     *
     * @param string|null $name
     *
     * @return TemplateInterface
     */
    public static function new(?string $name): TemplateInterface;

    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in an html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found, then that file will
     *       render the HTML for the component. For example, Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  Element::render(), ElementsBlock::render()
     */
    public function render(): ?string;

    /**
     * Sets the template page to use
     *
     * @param string|null $name
     *
     * @return static
     * @todo Implement! For now this just returns hard coded texts
     */
    public function setName(?string $name): static;

    /**
     * Returns the name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Returns the source string
     *
     * @return string|null
     */
    public function getSource(): ?string;

    /**
     * Sets the source string
     *
     * @param string|null $source
     *
     * @return static
     */
    public function setSource(?string $source): static;

    /**
     * Returns true if the object has been rendered (and Object::render() will return cached render data), false
     * otherwise
     *
     * @return bool
     */
    public function hasRendered(): bool;

    /**
     * Clears the render cache for this object
     *
     * @return static
     */
    public function clearRenderCache(): static;

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
     * @param bool      $readonly
     * @param bool|null $set_disabled
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null): static;

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
     * @param bool      $disabled
     * @param bool|null $set_readonly
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null): static;
}
