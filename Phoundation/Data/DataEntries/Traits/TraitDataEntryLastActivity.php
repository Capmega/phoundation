<?php

/**
 * Trait TraitDataEntryLastActivity
 *
 * This trait contains methods for DataEntry objects that require a last_activity entry
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;


trait TraitDataEntryLastActivity
{
    /**
     * Returns the string containing the last activity
     *
     * @return PhoDateTimeInterface|null
     */
    public function getLastActivity(): ?string
    {
        return $this->getTypesafe('string', 'last_activity');
    }


    /**
     * Returns the PhoDateTime object containing the last activity datetime
     *
     * @return PhoDateTimeInterface|null
     */
    public function getLastActivityObject(): ?PhoDateTimeInterface
    {
        return PhoDateTime::newOrNull($this->getLastActivity());
    }


    /**
     * Sets the string containing the last activity
     *
     * @param PhoDateTimeInterface|string|null $last_activity The last_activity value
     *
     * @return static
     */
    public function setLastActivity(PhoDateTimeInterface|string|null $last_activity): static
    {
        return $this->set(get_null(PhoDateTime::new($last_activity)->format('mysql')), 'last_activity');
    }
}
