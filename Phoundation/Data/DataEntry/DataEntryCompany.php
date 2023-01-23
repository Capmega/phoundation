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
     * @param Company|string|int|null $companies_id
     * @return static
     */
    public function setCompany(Company|string|int|null $companies_id): static
    {
        if (!is_numeric($companies_id)) {
            $companies_id = Company::get($companies_id);
        }

        if (is_object($companies_id)) {
            $companies_id = $companies_id->getId();
        }

        return $this->setDataValue('companies_id', $companies_id);
    }
}