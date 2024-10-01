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
     * @var string|false|null $status
     */
    protected string|false|null $status = null;


    /**
     * Returns the status
     *
     * @return string|false|null
     */
    public function getStatus(): string|false|null
    {
        return $this->status;
    }


    /**
     * Sets the status
     *
     * @param string|false|null $status
     *
     * @return static
     */
    public function setStatus(string|false|null $status): static
    {
        if ($status === 'all') {
            $status = false;
        }

        $this->status = $status;

        return $this;
    }
}
