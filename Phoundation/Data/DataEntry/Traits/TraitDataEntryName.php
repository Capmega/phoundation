<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Core;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Seo\Seo;

/**
 * Trait TraitDataEntryName
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryName
{
    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getValueTypesafe('string', 'seo_name');
    }


    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getValueTypesafe('string', 'name');
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
            if ($name === null) {
                $this->set('seo_name', null, true);

            } else {
                // Get SEO name and ensure that the seo_name does NOT surpass the name maxlength because MySQL won't find
                // the entry if it does!
                try {
                    $seo_name = Seo::unique(substr($name, 0, $this->definitions->get('name')
                                                                               ->getMaxlength()), static::getTable(), $this->getValueTypesafe('int', 'id'), 'seo_name');
                    $this->set('seo_name', $seo_name, true);
                } catch (SqlTableDoesNotExistException $e) {
                    // Crap, the table we're working on doesn't exist, WTF? No biggie, we're likely in init mode, and
                    // then we can ignore this issue as we're likely working from configuration instead
                    if (!Core::inInitState()) {
                        throw $e;
                    }
                }
            }
        }

        return $this->set('name', $name);
    }


    /**
     * Sets the seo_name for this object
     *
     * @note This method is protected because it should only be called from within DataEntry objects
     *
     * @param string|null $seo_name
     *
     * @return static
     */
    protected function setSeoName(?string $seo_name): static
    {
        return $this->set('seo_name', $seo_name);
    }
}
