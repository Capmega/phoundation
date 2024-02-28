<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataSource
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataSource
{
    protected string $source;


    /**
     * Returns the source
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }


    /**
     * Sets the source
     *
     * @param string $source
     * @return static
     */
    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }
}