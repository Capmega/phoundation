<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataBatch
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openbatch.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataBatch
{
    /**
     * @var bool $batch
     */
    protected bool $batch = false;


    /**
     * Returns if the scan job is a batch
     *
     * @return bool
     */
    public function getBatch(): bool
    {
        return $this->batch;
    }


    /**
     * Sets if the scan job is a batch
     *
     * @param bool $batch
     *
     * @return $this
     */
    public function setBatch(bool $batch): static
    {
        $this->batch = $batch;
        return $this;
    }
}