<?php

/**
 * Trait TraitDataEntryHostnamePort
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Seo\Seo;

trait TraitDataEntryHostname
{
    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string
    {
        return $this->getTypesafe('string', 'seo_hostname');
    }


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->getTypesafe('string', 'hostname');
    }


    /**
     * Sets the hostname for this object
     *
     * @param string|null $hostname
     *
     * @return static
     */
    public function setHostname(?string $hostname): static
    {
        if ($this->definitions->exists('seo_hostname')) {
            if ($hostname === null) {
                $this->set('seo_hostname', null);
            } else {
                $seo_hostname = Seo::unique($hostname, static::getTable(), $this->getTypesafe('int', 'id'), static::getUniqueField());
                $this->set('seo_hostname', $seo_hostname);
            }
        }

        return $this->set($hostname, 'hostname');
    }
}
