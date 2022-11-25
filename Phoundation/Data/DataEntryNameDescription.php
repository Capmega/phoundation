<?php

namespace Phoundation\Data;

use Phoundation\Seo\Seo;



/**
 * Trait DataEntryNameDescription
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryNameDescription
{
    /**
     * Returns the SEO name for this user
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getDataValue('seo_name');
    }



    /**
     * Returns the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getDataValue('name');
    }



    /**
     * Sets the name for this user
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        $seo_name = Seo::unique($name, $this->table, $this->id, $this->unique_column);

        $this->setDataValue('seo_name', $seo_name);
        return $this->setDataValue('name', $name);
    }



    /**
     * Returns the description for this user
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getDataValue('description');
    }



    /**
     * Sets the description for this user
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->setDataValue('description', $description);
    }
}