<?php

/**
 * Trait TraitMethodProcess
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


trait TraitMethodProcess
{
    /**
     * Tracks if this has been processed or not
     *
     * @var bool $is_processed
     */
    protected bool $is_processed = false;


    /**
     * Returns true if this has been processed
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->is_processed;
    }


    /**
     * Will process this
     *
     * @return static
     */
    public function process(): static
    {
        $this->is_processed = true;
        return $this;
    }
}
