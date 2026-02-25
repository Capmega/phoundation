<?php

/**
 * Trait TraitDataEntryCompany
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Business\Companies\Company;
use Phoundation\Business\Companies\Interfaces\CompanyInterface;


trait TraitDataEntryCompany
{
    /**
     * Setup virtual configuration for Companies
     *
     * @return static
     */
    protected function addVirtualConfigurationCompanies(): static
    {
        return $this->addVirtualConfiguration('companies', Company::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the companies_id column
     *
     * @return int|null
     */
    public function getCompaniesId(): ?int
    {
        return $this->getVirtualData('companies', 'int', 'id');
    }


    /**
     * Sets the companies_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCompaniesId(?int $id): static
    {
        return $this->setVirtualData('companies', $id, 'id');
    }


    /**
     * Returns the companies_code column
     *
     * @return string|null
     */
    public function getCompaniesCode(): ?string
    {
        return $this->getVirtualData('companies', 'string', 'code');
    }


    /**
     * Sets the companies_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCompaniesCode(?string $code): static
    {
        return $this->setVirtualData('companies', $code, 'code');
    }


    /**
     * Returns the companies_name column
     *
     * @return string|null
     */
    public function getCompaniesName(): ?string
    {
        return $this->getVirtualData('companies', 'string', 'name');
    }


    /**
     * Sets the companies_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCompaniesName(?string $name): static
    {
        return $this->setVirtualData('companies', $name, 'name');
    }


    /**
     * Returns the Company Object
     *
     * @return CompanyInterface|null
     */
    public function getCompanyObject(): ?CompanyInterface
    {
        return $this->getVirtualObject('companies');
    }


    /**
     * Returns the companies_id for this user
     *
     * @param CompanyInterface|null $_object
     *
     * @return static
     */
    public function setCompanyObject(?CompanyInterface $_object): static
    {
        return $this->setVirtualObject('companies', $_object);
    }
}
