<?php

/**
 * Trait TraitDataEntryStringPlatform
 *
 * This trait contains methods for DataEntry objects that require a platform
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Enums\EnumPlatformType;


trait TraitDataEntryStringPlatform
{
    /**
     * Returns the platform for this object
     *
     * @return string|null
     */
    public function getPlatform(): string|null
    {
        return $this->getTypesafe('string', 'platform');
    }


    /**
     * Sets the platform for this object
     *
     * @param EnumPlatformType|string|null $platform
     *
     * @return static
     */
    public function setPlatform(EnumPlatformType|string|null $platform): static
    {
        if ($platform) {
            if (is_object($platform)) {
                $platform = $platform->value;

            } else {
                // Test of the specified platform is valid
                $platform = EnumPlatformType::from($platform)->value;
            }
        }

        return $this->set(get_null($platform), 'platform');
    }
}
