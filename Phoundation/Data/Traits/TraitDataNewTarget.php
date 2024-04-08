<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait TraitDataNewTarget
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataNewTarget
{
    /**
     * The new target that should be executed because of this access denied
     *
     * @var string|int|null
     */
    protected string|int|null $new_target = null;


    /**
     * Returns the new target
     *
     * @return string|int|null
     */
    public function getNewTarget(): string|int|null
    {
        return $this->new_target;
    }


    /**
     * Sets the new target
     *
     * @param string|int|null $new_target
     *
     * @return static
     */
    public function setNewTarget(string|int|null $new_target): static
    {
        $this->new_target = $new_target;

        return $this;
    }
}