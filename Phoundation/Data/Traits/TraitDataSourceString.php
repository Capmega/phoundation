<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataSourceString
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataSourceString
{
    /**
     * Contains the source string for this object
     *
     * @var string $source
     */
    protected string $source;


    /**
     * Returns the source string
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }


    /**
     * Sets the source string
     *
     * @param string $source
     *
     * @return static
     */
    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }
}