<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait TraitObjectClass
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitObjectClass
{
    /**
     * The data entry class
     *
     * @var string|null $object_class
     */
    protected ?string $object_class = null;


    /**
     * Returns the data entry class
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return $this->object_class;
    }


    /**
     * Sets the data entry class
     *
     * @param string|null $object_class
     *
     * @return static
     */
    public function setObjectClass(?string $object_class): static
    {
        $this->object_class = $object_class;

        return $this;
    }
}
