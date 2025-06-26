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

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Core;
use Phoundation\Data\DataEntries\Exception\DataEntryNoSeoNameException;
use Phoundation\Databases\Sql\Exception\SqlNoDatabaseSelectedException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Utils\Seo;


trait TraitDataEntryName
{
    /**
     * Returns if this object supports SEO hostnames
     *
     * @return bool
     */
    public function getSupportsSeoName(): bool
    {
        return (bool) $this->getDefinitionsObject()->keyExists('seo_name');
    }


    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        if ($this->getSupportsSeoName()) {
            return $this->getTypesafe('string', 'seo_name');
        }

        throw new DataEntryNoSeoNameException(tr('Cannot return seo_name from ":class" DataEntry object, it does not have seo name support defined', [
            ':class' => $this::class
        ]));
    }


    /**
     * Returns the SEO name for this object
     *
     * @param string|null $seo_name
     * @return TraitDataEntryName
     */
    protected function setSeoName(?string $seo_name): static
    {
        if ($this->getSupportsSeoName()) {
            return $this->set(get_null($seo_name), 'seo_name', true);
        }

        throw new DataEntryNoSeoNameException(tr('Cannot set seo_name from ":class" DataEntry object, it does not have seo name support defined', [
            ':class' => $this::class
        ]));
    }


    /**
     * Returns the SEO name for this object
     *
     * @param string|null $name
     * @return TraitDataEntryName
     */
    protected function setSeoNameFromName(?string $name): static
    {
        // Get SEO name and ensure that the seo_name doesn't surpass the name maxlength because MySQL won't find
        // the entry if it does!
        try {
            if ($this->getSupportsSeoName() and $name) {
                if ($this->isLoadedFromConfiguration()) {
                    // ID is negative, this comes from configuration. Just use any seo-name
                    return $this->setSeoName(Seo::string($name));
                }

                $seo_name = Seo::unique(
                    substr($name, 0, $this->getDefinitionsObject()->get('name')->getMaxLength()),
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
     * Returns true if this object has the specified name
     *
     * @param string|null $name
     * @param bool        $strict
     *
     * @return bool
     */
    public function hasName(?string $name, bool $strict = true): bool
    {
        if ($strict) {
            return $this->getName() === $name;
        }

        return $this->getName() == $name;
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
        if ($this->getSupportsSeoName()) {
            if ($set_seo_name) {
                if (!$this->is_loading) {
                    $this->setSeoNameFromName($name);
                }
            }
        }

        return $this->set(get_null($name), 'name');
    }
}
