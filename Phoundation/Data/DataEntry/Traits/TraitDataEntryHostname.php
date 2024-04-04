<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Seo\Seo;


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
trait TraitDataEntryHostname
{
    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string
    {
        return $this->getSourceFieldValue('string', 'seo_hostname');
    }


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->getSourceFieldValue('string', 'hostname');
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
                $this->setSourceValue('seo_hostname', null);
            } else {
                $seo_hostname = Seo::unique($hostname, static::getTable(), $this->getSourceFieldValue('int', 'id'), static::getUniqueField());
                $this->setSourceValue('seo_hostname', $seo_hostname);
            }
        }

        return $this->setSourceValue('hostname', $hostname);
    }
}