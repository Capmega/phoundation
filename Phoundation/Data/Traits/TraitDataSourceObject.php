<?php

/**
 * Trait TraitDataSourceObject
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


trait TraitDataSourceObject
{
    /**
     * The source object
     *
     * @var object|null $source_object
     */
    protected ?object $source_object = null;


    /**
     * Returns the source object
     *
     * @return object
     */
    public function getSourceObject(): object
    {
        return $this->source_object;
    }


    /**
     * Sets the source object
     *
     * @param object|null $source_object
     *
     * @return static
     */
    public function setSourceObject(?object $source_object): static
    {
        $this->source_object = $source_object;

        return $this;
    }
}
