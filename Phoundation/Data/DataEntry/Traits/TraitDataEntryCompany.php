<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Company;

/**
 * Trait TraitDataEntryCompany
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryCompany
{
    /**
     * Returns the companies_id for this object
     *
     * @return int|null
     */
    public function getCompaniesId(): ?int
    {
        return $this->getValueTypesafe('int', 'companies_id');
    }


    /**
     * Sets the companies_id for this object
     *
     * @param int|null $companies_id
     *
     * @return static
     */
    public function setCompaniesId(?int $companies_id): static
    {
        return $this->set($companies_id, 'companies_id');
    }


    /**
     * Returns the companies_id for this object
     *
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        $companies_id = $this->getValueTypesafe('int', 'companies_id');
        if ($companies_id) {
            return new Company($companies_id);
        }

        return null;
    }


    /**
     * Returns the companies_name for this object
     *
     * @return string|null
     */
    public function getCompaniesName(): ?string
    {
        return $this->getValueTypesafe('string', 'companies_name');
    }


    /**
     * Sets the companies_name for this object
     *
     * @param string|null $companies_name
     *
     * @return static
     */
    public function setCompaniesName(?string $companies_name): static
    {
        return $this->set($companies_name, 'companies_name');
    }
}
