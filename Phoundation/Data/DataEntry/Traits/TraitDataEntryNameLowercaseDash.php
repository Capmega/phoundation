<?php

/**
 * Trait TraitDataEntryNameLowercaseDash
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Seo\Seo;

trait TraitDataEntryNameLowercaseDash
{
    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getTypesafe('string', 'seo_name');
    }


    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getTypesafe('string', 'name');
    }


    /**
     * Sets the name for this object
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setName(?string $name): static
    {
        if ($name === null) {
            $this->set(null, 'seo_name', true);

        } else {
            // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
            // the entry if it does!
            $name     = static::convertToLowerCaseDash($name);
            $seo_name = Seo::unique(substr($name, 0, $this->definitions->get('name')
                                                                       ->getMaxlength()), static::getTable(), $this->getTypesafe('int', 'id'), 'seo_name');
            $this->set($seo_name, 'seo_name', true);
        }

        return $this->set($name, 'name');
    }


    /**
     * Converts the given string to lowercase, dash separated string by replacing spaces and underscores to dashes
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     *
     * @return array|DataEntryInterface|string|int|null
     */
    protected static function convertToLowerCaseDash(array|DataEntryInterface|string|int|null $identifier): array|DataEntryInterface|string|int|null
    {
        if (!$identifier) {
            // NULL or "", return it
            return $identifier;
        }

        if (is_numeric($identifier)) {
            // This is a database id, return it
            return $identifier;
        }

        if (is_array($identifier)) {
            // This is an array identifier.
            if (array_key_exists('name', $identifier)) {
                $identifier['name'] = strtolower($identifier['name']);
                $identifier['name'] = str_replace([' ', '_'], '-', $identifier['name']);
            }

            return $identifier;
        }

        if ($identifier instanceof DataEntryInterface) {
            // This is a DataEntry object, return it
            return $identifier;
        }

        $identifier = strtolower($identifier);
        $identifier = str_replace([' ', '_'], '-', $identifier);

        return $identifier;
    }


    /**
     * Sets the seo_name for this object
     *
     * @note This method is protected because it should only be called from within DataEntry objects
     *
     * @param string|null $seo_name
     *
     * @return static
     */
    protected function setSeoName(?string $seo_name): static
    {
        return $this->set($seo_name, 'seo_name');
    }
}
