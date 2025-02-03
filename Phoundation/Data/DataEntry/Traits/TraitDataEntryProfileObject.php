<?php

/**
 * Trait TraitDataEntryProfileObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Hardware
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Plugins\Phoundation\Hardware\Devices\Interfaces\ProfileInterface;
use Plugins\Phoundation\Hardware\Devices\Profile;


trait TraitDataEntryProfileObject
{
    /**
     * Cache for the profile data
     *
     * @var ProfileInterface|null $o_profile
     */
    protected ?ProfileInterface $o_profile = null;


    /**
     * Returns the profiles_id for this object
     *
     * @return int|null
     */
    public function getProfilesId(): ?int
    {
        return $this->getTypesafe('int', 'profiles_id');
    }


    /**
     * Sets the profiles_id for this object
     *
     * @param int|null $id
     *
     * @return static
     */
    public function setProfilesId(?int $id): static
    {
        return $this->setProfileData(Profile::new()->loadOrNull($id));
    }


    /**
     * Returns the profiles_name for this profile
     *
     * @return string|null
     */
    public function getProfilesName(): ?string
    {
        return $this->getTypesafe('string', 'profiles_name');
    }


    /**
     * Sets the profiles_name for this profile
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setProfilesName(?string $name): static
    {
        return $this->setProfileData(Profile::new()->loadOrNull([
            'name' => $name
        ]));
    }


    /**
     * Returns the ProfileInterface for this object
     *
     * @return ProfileInterface|null
     */
    public function getProfile(): ?ProfileInterface
    {
        return $this->o_profile;
    }


    /**
     * Sets the ProfileInterface for this object
     *
     * @param ProfileInterface|null $profile
     *
     * @return static
     */
    public function setProfile(?ProfileInterface $profile): static
    {
        return $this->setProfileData($profile);
    }


    /**
     * Sets the profile data
     *
     * @param ProfileInterface|null $o_profile
     *
     * @return static
     */
    protected function setProfileData(?ProfileInterface $o_profile): static
    {
        $this->o_profile = $o_profile;

        return $this->set($o_profile?->getId(false), 'profiles_id')
                    ->set($o_profile?->getName()   , 'profiles_name');
    }
}
