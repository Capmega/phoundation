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

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Company;
use Phoundation\Business\Companies\Interfaces\CompanyInterface;


trait TraitDataEntryCompany
{
    /**
     * Company object cache
     *
     * @var CompanyInterface|null $o_company
     */
    protected ?CompanyInterface $o_company;


    /**
     * Returns the companies_id for this object
     *
     * @return int|null
     */
    public function getCompaniesId(): ?int
    {
        return $this->getTypesafe('int', 'companies_id');
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
        $this->o_company = null;
        return $this->set($companies_id, 'companies_id');
    }


    /**
     * Returns the company for this object
     *
     * @return CompanyInterface|null
     */
    public function getCompanyObject(): ?CompanyInterface
    {
        if (empty($this->o_company)) {
            $this->o_company = Company::new($this->getTypesafe('int', 'companies_id'))->loadOrNull();
        }

        return $this->o_company;
    }


    /**
     * Sets the company for this object
     *
     * @param CompanyInterface|null $o_company
     * @return TraitDataEntryCompany
     */
    public function setCompanyObject(?CompanyInterface $o_company): static
    {
        $this->setCompaniesId($o_company?->getId());

        $this->o_company = $o_company;
        return $this;
    }


    /**
     * Returns the companies_name for this object
     *
     * @return string|null
     */
    public function getCompaniesName(): ?string
    {
        return $this->getCompanyObject()->getName();
    }


    /**
     * Returns the companies_name for this object
     *
     * @param string|null $companies_name
     *
     * @return static
     */
    public function setCompaniesName(?string $companies_name): static
    {
        return $this->setCompanyObject(Company::new(['name' => $companies_name])->loadOrNull());
    }
}
