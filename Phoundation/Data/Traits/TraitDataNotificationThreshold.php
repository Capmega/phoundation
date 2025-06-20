<?php

/**
 * Trait TraitDataNotificationThreshold
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

trait TraitDataNotificationThreshold
{
    /**
     * Tracks the threshold for notifications
     *
     * @var int|null $notification_threshold
     */
    protected ?int $notification_threshold;


    /**
     * Returns the threshold for notifications
     *
     * @return int|null
     */
    public function getNotificationThreshold(): ?int
    {
        return $this->notification_threshold;
    }


    /**
     * Sets the threshold for notifications
     *
     * @param int|null $threshold
     *
     * @return static
     */
    public function setNotificationThreshold(?int $threshold): static
    {
        if (($threshold < 1) or ($threshold > 10)) {
            throw new OutOfBoundsException(tr('The specified notification threshold ":threshold" is invalid, it must be between 1 and 10', [
                ':threshold' => $threshold
            ]));
        }

        $this->notification_threshold = $threshold;
        return $this;
    }
}
