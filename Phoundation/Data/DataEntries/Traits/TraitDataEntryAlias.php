<?php

/**
 * Trait TraitDataEntryAlias
 *
 * This trait contains methods for DataEntry objects that require a alias
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Core;
use Phoundation\Databases\Sql\Exception\SqlNoDatabaseSelectedException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Seo\Seo;


trait TraitDataEntryAlias
{
    /**
     * Tracks if this class supports SEO hostaliass
     *
     * @var bool $supports_seo_alias
     */
    protected bool $supports_seo_alias = true;


    /**
     * Returns if this object supports SEO hostaliass
     *
     * @return bool
     */
    public function getSupportsSeoAlias(): bool
    {
        return $this->supports_seo_alias;
    }


    /**
     * Sets if this object supports SEO hostaliass
     *
     * @param bool $supports_seo_alias
     * @return static
     */
    public function setSupportsSeoAlias(bool $supports_seo_alias): static
    {
        $this->supports_seo_alias = $supports_seo_alias;
        return $this;
    }


    /**
     * Returns the SEO alias for this object
     *
     * @return string|null
     */
    public function getSeoAlias(): ?string
    {
        return $this->getTypesafe('string', 'seo_alias');
    }


    /**
     * Returns the SEO alias for this object
     *
     * @param string|null $seo_alias
     * @return TraitDataEntryAlias
     */
    protected function setSeoAlias(?string $seo_alias): static
    {
        return $this->set($seo_alias, 'seo_alias', true);
    }


    /**
     * Returns the SEO alias for this object
     *
     * @param string|null $alias
     * @return TraitDataEntryAlias
     */
    protected function setSeoAliasFromAlias(?string $alias): static
    {
        // Get SEO alias and ensure that the seo_alias does NOT surpass the alias maxlength because MySQL won't find
        // the entry if it does!
        try {
            if ($this->supports_seo_alias and $alias) {
                if ($this->isLoadedFromConfiguration()) {
                    // ID is negative, this comes from configuration. Just use any seo-alias
                    return $this->setSeoAlias(Seo::string($alias));
                }

                $seo_alias = Seo::unique(
                    substr($alias, 0, $this->definitions->get('alias')->getMaxlength()),
                    static::getTable(),
                    $this->getId(false),
                    'seo_alias'
                );

                return $this->setSeoAlias($seo_alias);
            }

        } catch (SqlNoDatabaseSelectedException |SqlUnknownDatabaseException | SqlTableDoesNotExistException $e) {
            // Crap, the table (or entire database!) that we're working on doesn't exist, WTF? No biggie, we're likely
            // in init mode, and then we can ignore this issue as we're likely working from configuration DataEntries
            // instead
            if (!Core::inInitState()) {
                throw $e;
            }
        }

        return $this->setSeoAlias(null);
    }


    /**
     * Returns the alias for this object
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->getTypesafe('string', 'alias');
    }


    /**
     * Sets the alias for this object
     *
     * @param string|null $alias
     * @param bool        $set_seo_alias
     *
     * @return static
     */
    public function setAlias(?string $alias, bool $set_seo_alias = true): static
    {
        if ($set_seo_alias) {
            // TODO This method does not exist anywhere?!
            $this->setSeoAliasFromAlias($alias);
        }

        return $this->set(get_null($alias), 'alias');
    }
}
