<?php

namespace Phoundation\Data\Traits;

/**
 * Trait DataArrayOutput
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataArrayOutput
{
    /**
     * The output for this object
     *
     * @var array|null $output
     */
    protected ?array $output = null;

    /**
     * Returns the output
     *
     * @return array|null
     */
    public function getOutput(): ?array
    {
        return $this->output;
    }
}