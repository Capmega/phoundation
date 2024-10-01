<?php

/**
 * Trait TraitDataSourceObjectClass
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataSourceObjectClass
{
    /**
     * The data entry class
     *
     * @var string|null $source_object_class
     */
    protected ?string $source_object_class = null;


    /**
     * Returns the data entry class
     *
     * @return string|null
     */
    public function getSourceObjectClass(): ?string
    {
        return $this->source_object_class;
    }


    /**
     * Sets the data entry class
     *
     * @param string|null $source_object_class
     *
     * @return static
     */
    public function setSourceObjectClass(?string $source_object_class): static
    {
        $this->source_object_class = $source_object_class;

        return $this;
    }
}
