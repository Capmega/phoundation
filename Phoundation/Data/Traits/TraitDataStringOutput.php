<?php

/**
 * Trait TraitDataStringOutput
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


trait TraitDataStringOutput
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
