<?php

/**
 * Trait TraitDataEntryHostname
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Core;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Seo\Seo;


trait TraitDataEntryHostname
{
    /**
     * Tracks if this class supports SEO hostnames
     *
     * @var bool $supports_seo_hostname
     */
    protected bool $supports_seo_hostname = true;


    /**
     * Returns if this object supports SEO hostnames
     *
     * @return bool
     */
    public function getSupportsSeoHostname(): bool
    {
        return $this->supports_seo_hostname;
    }


    /**
     * Sets if this object supports SEO hostnames
     *
     * @param bool $supports_seo_hostname
     * @return static
     */
    public function setSupportsSeoHostname(bool $supports_seo_hostname): static
    {
        $this->supports_seo_hostname = $supports_seo_hostname;
        return $this;
    }


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
     * Sets the SEO hostname for this object
     *
     * @param string|null $seo_hostname
     * @return static
     */
    protected function setSeoHostname(?string $seo_hostname): static
    {
        return $this->set(get_null($seo_hostname), 'seo_hostname');
    }


    /**
     * Sets the SEO hostname for this object
     *
     * @param string|null $hostname
     * @return static
     */
    protected function setSeoHostnameFromHostname(?string $hostname): static
    {
        // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
        // the entry if it does!
        try {
            if ($hostname) {
                $seo_hostname = Seo::unique(
                    substr($hostname, 0, $this->definitions->get('hostname')->getMaxlength()),
                    static::getTable(),
                    $this->getId(false),
                    'seo_hostname'
                );

                return $this->setSeoHostname($seo_hostname);
            }

        } catch (SqlTableDoesNotExistException $e) {
            // Crap, the table we're working on doesn't exist, WTF? No biggie, we're likely in init mode, and
            // then we can ignore this issue as we're likely working from configuration instead
            if (!Core::inInitState()) {
                throw $e;
            }

        }

        return $this->setSeoHostname(null);
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
     * @param bool $set_seo_name
     * @return static
     */
    public function setHostname(?string $hostname, ?bool $set_seo_name = null): static
    {
        if ($set_seo_name === null) {
            $set_seo_name = $this->supports_seo_hostname;
        }

        if ($set_seo_name) {
            if (!$this->is_loading) {
                $this->setSeoHostnameFromHostname($hostname);
            }
        }

        return $this->set(get_null($hostname), 'hostname');
    }
}
