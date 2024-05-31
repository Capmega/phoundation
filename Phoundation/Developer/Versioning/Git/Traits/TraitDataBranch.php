<?php

/**
 * Trait TraitDataBranch
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

trait TraitDataBranch
{
    /**
     * @var string $branch
     */
    protected string $branch;


    /**
     * Returns the source
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch;
    }


    /**
     * Sets the source
     *
     * @param string $branch
     *
     * @return static
     */
    public function setBranch(string $branch): static
    {
        $this->branch = $branch;

        return $this;
    }
}
