<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Business\Companies\Company;



/**
 * Trait DataEntryCompany
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCompany
{
    /**
     * The company for this object
     *
     * @var Company|null $company
     */
    protected ?Company $company;



    /**
     * Returns the companies_id for this object
     *
     * @return string|null
     */
    public function getCompaniesId(): ?string
    {
        return $this->getDataValue('companies_id');
    }



    /**
     * Sets the companies_id for this object
     *
     * @param string|null $companies_id
     * @return static
     */
    public function setCompaniesId(?string $companies_id): static
    {
        return $this->setDataValue('companies_id', $companies_id);
    }



    /**
     * Returns the companies_id for this object
     *
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        $companies_id = $this->getDataValue('companies_id');

        if ($companies_id) {
            return new Company($companies_id);
        }

        return null;
    }



    /**
     * Sets the companies_id for this object
     *
     * @param Company|null $company
     * @return static
     */
    public function setCompany(?Company $company): static
    {
        if (is_object($company)) {
            $company = $company->getId();
        }

        return $this->setDataValue('companies_id', $company);
    }
}