<?php

/**
 * interface SelectedInterface
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

use Phoundation\Data\Interfaces\IteratorInterface;


interface SelectedInterface
{
    /**
     * Clear multiple selected options
     *
     * @return static
     */
    public function clearSelected(): static;

    /**
     * Returns the selected option(s)
     *
     * @return array|string|float|int|null
     */
    public function getSelected(): array|string|float|int|null;

    /**
     * Sets multiple selected options
     *
     * @param IteratorInterface|array|string|float|int|null $selected
     * @param bool                                          $value
     *
     * @return static
     */
    public function setSelected(IteratorInterface|array|string|float|int|null $selected = null, bool $value = false): static;

    /**
     * Adds a single or multiple selected options
     *
     * @param IteratorInterface|array|string|float|int|null $selected
     * @param bool                                          $value
     *
     * @return static
     */
    public function addSelected(IteratorInterface|array|string|float|int|null $selected, bool $value = false): static;
}
