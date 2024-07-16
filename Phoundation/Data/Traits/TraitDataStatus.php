<?php

/**
 * Trait TraitDataStatus
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openstatus.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataStatus
{
    /**
     * The status to use
     *
     * @var string|null $status
     */
    protected ?string $status = null;


    /**
     * Returns the status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }


    /**
     * Sets the status
     *
     * @param string|null $status
     *
     * @return static
     */
    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
}