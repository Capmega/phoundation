<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Plugins\Hardware\Devices\Interfaces\ProfileInterface;
use Plugins\Hardware\Devices\Profile;


/**
 * Trait DataEntryProfileObject
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Hardware
 */
trait DataEntryProfileObject
{
    /**
     * Returns the profiles_id for this object
     *
     * @return int|null
     */
    public function getProfilesId(): ?int
    {
        return $this->getSourceValueTypesafe('int', 'profiles_id');
    }


    /**
     * Sets the profiles_id for this object
     *
     * @param int|null $profiles_id
     * @return static
     */
    public function setProfilesId(?int $profiles_id): static
    {
        return $this->setSourceValue('profiles_id', $profiles_id);
    }


    /**
     * Returns the profiles_id for this profile
     *
     * @return ProfileInterface|null
     */
    public function getProfile(): ?ProfileInterface
    {
        $profiles_id = $this->getSourceValueTypesafe('int', 'profiles_id');

        if ($profiles_id) {
            return Profile::get($profiles_id,  'id');
        }

        return null;
    }


    /**
     * Returns the profiles_name for this profile
     *
     * @return string|null
     */
    public function getProfilesName(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'profiles_name');
    }


    /**
     * Sets the profiles_name for this profile
     *
     * @param string|null $profiles_name
     * @return static
     */
    public function setProfilesName(?string $profiles_name): static
    {
        return $this->setSourceValue('profiles_name', $profiles_name);
    }
}
