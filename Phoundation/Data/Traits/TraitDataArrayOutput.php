<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataArrayOutput
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataArrayOutput
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