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
     * @var string|false|null $status_filter
     */
    protected string|false|null $status_filter = false;


    /**
     * Returns the status
     *
     * @return string|false|null
     */
    public function getStatusFilter(): string|false|null
    {
        return $this->status_filter;
    }


    /**
     * Sets the status
     *
     * @param string|false|null $status_filter
     *
     * @return static
     */
    public function setStatusFilter(string|false|null $status_filter): static
    {
        if ($status_filter === 'all') {
            $status_filter = false;
        }

        $this->status_filter = $status_filter;

        return $this;
    }
}
