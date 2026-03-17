<?php

/**
 * Trait TraitDataExecuted
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataExecuted
{
    /**
     * @var bool $executed
     */
    protected bool $executed;


    /**
     * Returns the source
     *
     * @return bool
     */
    public function getExecuted(): bool
    {
        return $this->executed;
    }


    /**
     * Sets the source
     *
     * @param bool $executed
     *
     * @return static
     */
    public function setExecuted(bool $executed): static
    {
        $this->executed = $executed;
        return $this;
    }
}
