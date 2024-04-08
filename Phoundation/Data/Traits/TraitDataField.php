<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait TraitDataField
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openfield.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataField
{
    /**
     * The field to use
     *
     * @var string|null $field
     */
    protected ?string $field;


    /**
     * Returns the field
     *
     * @return string|null
     */
    public function getColumn(): ?string
    {
        return $this->field;
    }


    /**
     * Sets the field
     *
     * @param string|null $field
     *
     * @return static
     */
    public function setColumn(?string $field): static
    {
        $this->field = $field;

        return $this;
    }
}
