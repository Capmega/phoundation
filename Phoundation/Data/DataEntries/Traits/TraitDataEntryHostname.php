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
use Phoundation\Data\DataEntries\Exception\DataEntryNoSeoNameException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Utils\Seo;


trait TraitDataEntryHostname
{
    /**
     * Returns if this object supports SEO hostnames
     *
     * @return bool
     */
    public function getSupportsSeoHostname(): bool
    {
        return (bool) $this->getDefinitionsObject()->keyExists('seo_hostname');
    }


    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string
    {
        if ($this->getSupportsSeoName()) {
            return $this->getTypesafe('string', 'seo_hostname');
        }

        throw new DataEntryNoSeoNameException(tr('Cannot return seo_hostname from ":class" DataEntry object, it does not have seo hostname support defined', [
            ':class' => $this::class
        ]));
    }


    /**
     * Sets the SEO hostname for this object
     *
     * @param string|null $seo_hostname
     * @return static
     */
    protected function setSeoHostname(?string $seo_hostname): static
    {
        if ($this->getSupportsSeoName()) {
            return $this->set(get_null($seo_hostname), 'seo_hostname');
        }

        throw new DataEntryNoSeoNameException(tr('Cannot set seo_hostname from ":class" DataEntry object, it does not have seo hostname support defined', [
            ':class' => $this::class
        ]));
    }


    /**
     * Sets the SEO hostname for this object
     *
     * @param string|null $hostname
     * @return static
     */
    protected function setSeoHostnameFromHostname(?string $hostname): static
    {
        // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL will not find
        // the entry if it does!
        try {
            if ($hostname) {
                $seo_hostname = Seo::unique(
                    substr($hostname, 0, $this->getDefinitionsObject()->get('hostname')->getMaxLength()),
                    static::getTable(),
                    $this->getId(false),
                    'seo_hostname'
                );

                return $this->setSeoHostname($seo_hostname);
            }

        } catch (SqlTableDoesNotExistException $e) {
            // Crap, the table we are working on does not exist, WTF? No biggie, we are likely in init mode, and
            // then we can ignore this issue as we are likely working from configuration instead
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
            $set_seo_name = $this->getSupportsSeoHostname();
        }

        if ($set_seo_name) {
            if (!$this->is_loading) {
                $this->setSeoHostnameFromHostname($hostname);
            }
        }

        return $this->set(get_null($hostname), 'hostname');
    }
}
