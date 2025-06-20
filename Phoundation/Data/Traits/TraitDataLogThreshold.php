<?php

/**
 * Trait TraitDataLogThreshold
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


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataLogThreshold
{
    /**
     * Tracks the threshold for logging
     *
     * @var int|null $log_threshold
     */
    protected ?int $log_threshold;


    /**
     * Returns the threshold for logging
     *
     * @return int|null
     */
    public function getLogThreshold(): ?int
    {
        return $this->log_threshold;
    }


    /**
     * Sets the threshold for logging
     *
     * @param int|null $threshold
     *
     * @return static
     */
    public function setLogThreshold(?int $threshold): static
    {
        if (($threshold < 1) or ($threshold > 10)) {
            throw new OutOfBoundsException(tr('The specified log threshold ":threshold" is invalid, it must be between 1 and 10', [
                ':threshold' => $threshold
            ]));
        }

        $this->log_threshold = $threshold;
        return $this;
    }
}
