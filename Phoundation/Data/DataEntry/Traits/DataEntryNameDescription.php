<?php

namespace Phoundation\Data\DataEntry\Traits;

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
    use DataEntryDescription;



    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getDataValue('seo_name');
    }



    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getDataValue('name');
    }



    /**
     * Sets the name for this object
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        if ($name !== null) {
            $seo_name = Seo::unique($name, $this->table, $this->getDataValue('id'), $this->unique_column);
            $this->setDataValue('seo_name', $seo_name);
        }

        return $this->setDataValue('name', $name);
    }
}