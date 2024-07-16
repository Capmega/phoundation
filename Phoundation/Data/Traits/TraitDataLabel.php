<?php

/**
 * Trait TraitDataLabel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openlabel.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataLabel
{
    /**
     * The label to use
     *
     * @var string|null $label
     */
    protected ?string $label = null;


    /**
     * Returns the label
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }


    /**
     * Sets the label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }
}