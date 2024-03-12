<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait TraitDataMaxIdRetries
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openmax_id_retries.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataMaxIdRetries
{
    /**
     * Tracks how many random id retries to perform
     *
     * @var int $max_id_retries
     */
    protected int $max_id_retries = 5;


    /**
     * Returns how many random id retries to perform
     *
     * @return int
     */
    public function getMaxIdRetries(): int
    {
        return $this->max_id_retries;
    }


    /**
     * Sets how many random id retries to perform
     *
     * @param int $max_id_retries
     * @return static
     */
    public function setMaxIdRetries(int $max_id_retries): static
    {
        if ($max_id_retries < 0) {
            throw new OutOfBoundsException(tr('Specified value ":value" for max id retries is invalid, it must be a positive integer number', [
                ':value' => $max_id_retries
            ]));
        }

        $this->max_id_retries = $max_id_retries;
        return $this;
    }
}