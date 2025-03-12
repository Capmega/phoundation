<?php

/**
 * interface ValueInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

use Stringable;


interface ValueInterface
{
    /**
     * Returns the value for the input element
     *
     * @return Stringable|string|float|int|null
     */
    public function getValue(): Stringable|string|float|int|null;

    /**
     * Sets the value for the input element
     *
     * @param Stringable|string|float|int|null $value
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = true): static;

    /**
     * Returns the HTML "null_display" element attribute
     *
     * @return Stringable|string|float|int|null
     */
    public function getNullDisplay(): Stringable|string|float|int|null;

    /**
     * Set the HTML "null_display" element attribute
     *
     * @param Stringable|string|float|int|null $null_display
     *
     * @return static
     */
    public function setNullDisplay(Stringable|string|float|int|null $null_display): static;
}
