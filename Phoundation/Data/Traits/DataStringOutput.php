<?php

namespace Phoundation\Data\Traits;

/**
 * Trait DataStringOutput
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataStringOutput
{
    /**
     * The output for this object
     *
     * @var string|null $output
     */
    protected ?string $output = null;

    /**
     * Returns the output
     *
     * @return string|null
     */
    public function getOutput(): ?string
    {
        return $this->output;
    }
}