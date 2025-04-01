<?php

/**
 * Trait TraitDataEntryVersion
 *
 * This trait contains methods for DataEntry objects that require a version and description
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Seo\Seo;


trait TraitDataEntryVersion
{
    /**
     * Returns the SEO version for this object
     *
     * @return string|null
     */
    public function getSeoVersion(): ?string
    {
        return $this->getTypesafe('string', 'seo_version');
    }


    /**
     * Returns the version for this object
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->getTypesafe('string', 'version');
    }


    /**
     * Sets the version for this object
     *
     * @param string|null $version
     *
     * @return static
     */
    public function setVersion(?string $version): static
    {
        if (empty($version)) {
            return $this->set(null, 'seo_version');
        }

        if (!$this->is_loading) {
            // Get SEO version and ensure that the seo_version does NOT surpass the version maxlength because MySQL
            // won't find the entry if it does!
            $seo_version = Seo::unique(
                substr($version, 0, $this->getDefinitionsObject()->get('version')->getMaxlength()),
                static::getTable(),
                $this->getId(false),
                'seo_version'
            );

            $this->setSeoVersion($seo_version);
        }

        return $this->set($version, 'version');
    }


    /**
     * Sets the seo_version for this object
     *
     * @note This method is protected because it should only be called from within DataEntry objects
     *
     * @param string|null $seo_version
     *
     * @return static
     */
    protected function setSeoVersion(?string $seo_version): static
    {
        return $this->set(get_null($seo_version), 'seo_version');
    }
}
