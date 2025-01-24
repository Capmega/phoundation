<?php

/**
 * Trait DataEntryCounty
 *
 * This trait contains methods for DataEntry objects that require GEO county data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Counties\County;
use Phoundation\Geo\Counties\Interfaces\CountyInterface;


trait TraitDataEntryCounty
{
    /**
     * County object cache
     *
     * @var CountyInterface|null $o_county
     */
    protected ?CountyInterface $o_county;


    /**
     * Returns the counties_id for this object
     *
     * @return int|null
     */
    public function getCountiesId(): ?int
    {
        return $this->getTypesafe('int', 'counties_id');
    }


    /**
     * Sets the counties_id for this object
     *
     * @param int|null $counties_id
     *
     * @return static
     */
    public function setCountiesId(?int $counties_id): static
    {
        $this->o_county = null;
        return $this->set($counties_id, 'counties_id');
    }


    /**
     * Returns the county for this object
     *
     * @return CountyInterface|null
     */
    public function getCountyObject(): ?CountyInterface
    {
        if (empty($this->o_county)) {
            $this->o_county = County::new($this->getTypesafe('int', 'counties_id'))->loadOrNull();
        }

        return $this->o_county;
    }


    /**
     * Sets the county for this object
     *
     * @param CountyInterface|null $o_county
     * @return TraitDataEntryCounty
     */
    public function setCountyObject(?CountyInterface $o_county): static
    {
        $this->setCountiesId($o_county?->getId());

        $this->o_county = $o_county;
        return $this;
    }


    /**
     * Returns the counties_name for this object
     *
     * @return string|null
     */
    public function getCountiesName(): ?string
    {
        return $this->getCountyObject()->getName();
    }


    /**
     * Returns the counties_name for this object
     *
     * @param string|null $counties_name
     *
     * @return static
     */
    public function setCountiesName(?string $counties_name): static
    {
        return $this->setCountyObject(County::new(['name' => $counties_name])->loadOrNull());
    }
}
