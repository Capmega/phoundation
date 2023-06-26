<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Seo\Seo;


/**
 * Trait DataEntryName
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryName
{
    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getDataValue('string', 'seo_name');
    }


    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getDataValue('string', 'name');
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
            // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
            // the entry if it does!
            $seo_name = Seo::unique(substr($name, 0, $this->definitions->get('name')->getMaxlength()), $this->table, $this->getDataValue('int', 'id'), 'seo_name');
            $this->setDataValue('seo_name', $seo_name);
        }

        return $this->setDataValue('name', $name);
    }
}