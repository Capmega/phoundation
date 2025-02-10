<?php

/**
 * Trait TraitDataEntryAlias
 *
 * This trait contains methods for DataEntry objects that require a alias and description
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryAlias
{
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
