<?php

/**
 * Trait TraitDataEntryName
 *
 * This trait contains methods for DataEntry objects that require a name
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Core;
use Phoundation\Databases\Sql\Exception\SqlNoDatabaseSelectedException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Seo\Seo;


trait TraitDataEntryName
{
    /**
     * Tracks if this class supports SEO hostnames
     *
     * @var bool $supports_seo_name
     */
    protected bool $supports_seo_name = true;


    /**
     * Returns if this object supports SEO hostnames
     *
     * @return bool
     */
    public function getSupportsSeoName(): bool
    {
        return $this->supports_seo_name;
    }


    /**
     * Sets if this object supports SEO hostnames
     *
     * @param bool $supports_seo_name
     * @return static
     */
    public function setSupportsSeoName(bool $supports_seo_name): static
    {
        $this->supports_seo_name = $supports_seo_name;
        return $this;
    }


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
     * Returns the SEO name for this object
     *
     * @param string|null $seo_name
     * @return TraitDataEntryName
     */
    protected function setSeoName(?string $seo_name): static
    {
        return $this->set(get_null($seo_name), 'seo_name', true);
    }


    /**
     * Returns the SEO name for this object
     *
     * @param string|null $name
     * @return TraitDataEntryName
     */
    protected function setSeoNameFromName(?string $name): static
    {
        // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
        // the entry if it does!
        try {
            if ($this->supports_seo_name and $name) {
                if ($this->isLoadedFromConfiguration()) {
                    // ID is negative, this comes from configuration. Just use any seo-name
                    return $this->setSeoName(Seo::string($name));
                }

                $seo_name = Seo::unique(
                    substr($name, 0, $this->definitions->get('name')->getMaxlength()),
                    static::getTable(),
                    $this->getId(false),
                    'seo_name'
                );

                return $this->setSeoName($seo_name);
            }

        } catch (SqlNoDatabaseSelectedException |SqlUnknownDatabaseException | SqlTableDoesNotExistException $e) {
            // Crap, the table (or entire database!) that we're working on doesn't exist, WTF? No biggie, we're likely
            // in init mode, and then we can ignore this issue as we're likely working from configuration DataEntries
            // instead
            if (!Core::inInitState()) {
                throw $e;
            }
        }

        return $this->setSeoName(null);
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
     * @param bool        $set_seo_name
     *
     * @return static
     */
    public function setName(?string $name, bool $set_seo_name = true): static
    {
        if ($set_seo_name) {
            $this->setSeoNameFromName($name);
        }

        return $this->set(get_null($name), 'name');
    }
}
