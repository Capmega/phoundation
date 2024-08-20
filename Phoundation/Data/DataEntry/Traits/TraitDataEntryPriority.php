<?php

/**
 * Trait TraitDataEntryPriority
 *
 * This trait contains methods for DataEntry objects that require a priority
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;

trait TraitDataEntryPriority
{
    /**
     * The default priority if none was specified
     *
     * @var int|null $default_priority
     */
    private ?int $default_priority = 50;

    /**
     * The minimum value for priorities
     *
     * @var int $min_priority
     */
    protected int $min_priority = 0;

    /**
     * The maximum value for priorities
     *
     * @var int $max_priority
     */
    protected int $max_priority = 100;


    /**
     * Returns the default priority for this object
     *
     * @param int|null $default_priority
     *
     * @return static
     */
    protected function setDefaultPriority(?int $default_priority): static
    {
        if (is_numeric($default_priority) and (($default_priority < $this->min_priority) or ($default_priority > $this->max_priority))) {
            throw new OutOfBoundsException(tr('Specified ":class" class default priority ":priority" is invalid, it should be a number from ":min" to ":max"', [
                ':class'    => static::class,
                ':priority' => $default_priority,
                ':min'      => $this->min_priority,
                ':max'      => $this->max_priority
            ]));
        }

        $this->default_priority = $default_priority;
        return $this;
    }


    /**
     * Returns the default priority for this object
     *
     * @return int|null
     */
    public function getDefaultPriority(): ?int
    {
        return $this->default_priority;
    }


    /**
     * Returns the minimum priority for this object
     *
     * @return int
     */
    public function getMinPriority(): int
    {
        return $this->min_priority;
    }


    /**
     * Returns the maximum priority for this object
     *
     * @return int
     */
    public function getMaxPriority(): int
    {
        return $this->max_priority;
    }


    /**
     * Returns the priority for this object
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->getTypesafe('int', 'priority', $this->default_priority);
    }


    /**
     * Sets the priority for this object
     *
     * @param int|null $priority
     *
     * @return static
     */
    public function setPriority(?int $priority): static
    {
        if (is_numeric($priority)) {
            if (($priority < $this->min_priority) or ($priority > $this->max_priority)) {
                throw new OutOfBoundsException(tr('Specified ":class" class priority ":priority" is invalid, it should be a number from ":min" to ":max"', [
                    'class'     => static::class,
                    ':priority' => $priority,
                    ':min'      => $this->min_priority,
                    ':max'      => $this->max_priority
                ]));
            }

        } else {
            // Use the default priority, but test it first!
            $priority = $this->default_priority;
        }

        return $this->set($priority, 'priority');
    }
}
