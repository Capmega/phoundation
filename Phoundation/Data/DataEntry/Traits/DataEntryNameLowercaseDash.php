<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Seo\Seo;


/**
 * Trait DataEntryNameLowercaseDash
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryNameLowercaseDash
{
    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getSourceColumnValue('string', 'seo_name');
    }


    /**
     * Sets the seo_name for this object
     *
     * @note This method is protected because it should only be called from within DataEntry objects
     * @param string|null $seo_name
     * @return static
     */
    protected function setSeoName(?string $seo_name): static
    {
        return $this->setSourceValue('seo_name', $seo_name);
    }


    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getSourceColumnValue('string', 'name');
    }


    /**
     * Sets the name for this object
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        if ($name === null) {
            $this->setSourceValue('seo_name', null, true);

        } else {
            // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
            // the entry if it does!
            $name     = static::convertToLowerCaseDash($name);
            $seo_name = Seo::unique(substr($name, 0, $this->definitions->get('name')->getMaxlength()), static::getTable(), $this->getSourceColumnValue('int', 'id'), 'seo_name');

            $this->setSourceValue('seo_name', $seo_name, true);
        }

        return $this->setSourceValue('name', $name);
    }


    /**
     * Converts the given string to lowercase, dash separated string by replacing spaces and underscores to dashes
     *
     * @param DataEntryInterface|string|null $source
     * @return DataEntryInterface|string|null
     */
    protected static function convertToLowerCaseDash(DataEntryInterface|string|null $source): DataEntryInterface|string|null
    {
        if (!$source) {
            // NULL or "", just return it
            return $source;
        }

        if ($source instanceof DataEntryInterface) {
            // This is a DataEntry object, just return it
            return $source;
        }

        $source = strtolower($source);
        $source = str_replace([' ', '_'], '-', $source);

        return $source;
    }
}
