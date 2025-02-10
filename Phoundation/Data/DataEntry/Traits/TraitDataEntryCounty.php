<?php

/**
 * Trait TraitDataEntryCounty
 *
 * This trait contains methods for DataEntry objects that require a county
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Counties\County;
use Phoundation\Geo\Counties\Interfaces\CountyInterface;


trait TraitDataEntryCounty
{
    /**
     * Setup virtual configuration for Counties
     *
     * @return static
     */
    protected function addVirtualConfigurationCounties(): static
    {
        return $this->addVirtualConfiguration('counties', County::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the counties_id column
     *
     * @return int|null
     */
    public function getCountiesId(): ?int
    {
        return $this->getVirtualData('counties', 'int', 'id');
    }


    /**
     * Sets the counties_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCountiesId(?int $id): static
    {
        return $this->setVirtualData('counties', $id, 'id');
    }


    /**
     * Returns the counties_code column
     *
     * @return string|null
     */
    public function getCountiesCode(): ?string
    {
        return $this->getVirtualData('counties', 'string', 'code');
    }


    /**
     * Sets the counties_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCountiesCode(?string $code): static
    {
        return $this->setVirtualData('counties', $code, 'code');
    }


    /**
     * Returns the counties_name column
     *
     * @return string|null
     */
    public function getCountiesName(): ?string
    {
        return $this->getVirtualData('counties', 'string', 'name');
    }


    /**
     * Sets the counties_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCountiesName(?string $name): static
    {
        return $this->setVirtualData('counties', $name, 'name');
    }


    /**
     * Returns the County Object
     *
     * @return CountyInterface|null
     */
    public function getCountyObject(): ?CountyInterface
    {
        return $this->getVirtualObject('counties');
    }


    /**
     * Returns the counties_id for this user
     *
     * @param CountyInterface|null $o_object
     *
     * @return static
     */
    public function setCountyObject(?CountyInterface $o_object): static
    {
        return $this->setVirtualObject('counties', $o_object);
    }
}
