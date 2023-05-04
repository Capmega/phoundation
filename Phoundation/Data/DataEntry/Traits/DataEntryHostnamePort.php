<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Phoundation\Seo\Seo;

/**
 * Trait DataEntryHostnamePort
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryHostnamePort
{
    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string
    {
        return $this->getDataValue('seo_hostname');
    }


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->getDataValue('hostname');
    }


    /**
     * Sets the hostname for this object
     *
     * @param string|null $hostname
     * @return static
     */
    public function setHostname(?string $hostname): static
    {
        if ($hostname === null) {
            $this->setDataValue('seo_hostname', null);
        } else {
            $seo_hostname = Seo::unique($hostname, $this->table, $this->getDataValue('id'), $this->unique_field);
            $this->setDataValue('seo_hostname', $seo_hostname);
        }

        return $this->setDataValue('hostname', $hostname);
    }


    /**
     * Returns the port for this object
     *
     * @return string|null
     */
    public function getPort(): ?string
    {
        return $this->getDataValue('port');
    }


    /**
     * Sets the port for this object
     *
     * @param string|null $port
     * @return static
     */
    public function setPort(?string $port): static
    {
        return $this->setDataValue('port', $port);
    }
}