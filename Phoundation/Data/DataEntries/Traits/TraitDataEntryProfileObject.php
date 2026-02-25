<?php

/**
 * Trait TraitDataEntryProfile
 *
 * This trait contains methods for DataEntry objects that require an profile
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Plugins\Phoundation\Hardware\Devices\Interfaces\ProfileInterface;
use Plugins\Phoundation\Hardware\Devices\Profile;


trait TraitDataEntryProfileObject
{
    /**
     * Setup virtual configuration for Profiles
     *
     * @return static
     */
    protected function addVirtualConfigurationProfiles(): static
    {
        return $this->addVirtualConfiguration('profiles', Profile::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the profiles_id column
     *
     * @return int|null
     */
    public function getProfilesId(): ?int
    {
        return $this->getVirtualData('profiles', 'int', 'id');
    }


    /**
     * Sets the profiles_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setProfilesId(?int $id): static
    {
        return $this->setVirtualData('profiles', $id, 'id');
    }


    /**
     * Returns the profiles_code column
     *
     * @return string|null
     */
    public function getProfilesCode(): ?string
    {
        return $this->getVirtualData('profiles', 'string', 'code');
    }


    /**
     * Sets the profiles_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setProfilesCode(?string $code): static
    {
        return $this->setVirtualData('profiles', $code, 'code');
    }


    /**
     * Returns the profiles_name column
     *
     * @return string|null
     */
    public function getProfilesName(): ?string
    {
        return $this->getVirtualData('profiles', 'string', 'name');
    }


    /**
     * Sets the profiles_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setProfilesName(?string $name): static
    {
        return $this->setVirtualData('profiles', $name, 'name');
    }


    /**
     * Returns the Profile Object
     *
     * @return ProfileInterface|null
     */
    public function getProfileObject(): ?ProfileInterface
    {
        return $this->getVirtualObject('profiles');
    }


    /**
     * Returns the profiles_id for this user
     *
     * @param ProfileInterface|null $_object
     *
     * @return static
     */
    public function setProfileObject(?ProfileInterface $_object): static
    {
        return $this->setVirtualObject('profiles', $_object);
    }
}
