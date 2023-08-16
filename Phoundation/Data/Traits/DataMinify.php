<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataMinify
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openminify.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataMinify
{
    /**
     * @var bool $minify
     */
    protected bool $minify = false;


    /**
     * Returns the minify value
     *
     * @return bool
     */
    public function getMinify(): bool
    {
        return $this->minify;
    }


    /**
     * Sets the minify value
     *
     * @param bool $minify
     * @return static
     */
    public function setMinify(bool $minify): static
    {
        $this->minify = $minify;
        return $this;
    }
}