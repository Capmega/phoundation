<?php

/**
 * Trait TraitDataSelector
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataSelector
{
    /**
     * Tracks the selector
     *
     * @var string|null $selector
     */
    protected ?string $selector = null;

    /**
     * Tracks the selector suffix
     *
     * @var string|null $selector_suffix
     */
    protected ?string $selector_suffix = null;


    /**
     * Returns the selector
     *
     * @return string|null
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }


    /**
     * Sets the selector
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setSelector(?string $selector): static
    {
        $this->selector = get_null($selector);
        return $this;
    }


    /**
     * Returns the selector_suffix
     *
     * @return string|null
     */
    public function getSelectorSuffix(): ?string
    {
        return $this->selector_suffix;
    }


    /**
     * Sets the source
     *
     * @param string|null $selector_suffix
     *
     * @return static
     */
    public function setSelectorSuffix(?string $selector_suffix): static
    {
        $this->selector_suffix = get_null($selector_suffix);
        return $this;
    }
}
