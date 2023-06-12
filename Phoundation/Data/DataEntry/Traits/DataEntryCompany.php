<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Companies\Company;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Trait DataEntryCompany
 *
 * This trait contains methods for DataEntry objects that require a company
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @return int|null
     */
    public function getCompaniesId(): ?int
    {
        return $this->getDataValue('string', 'companies_id');
    }


    /**
     * Sets the companies_id for this object
     *
     * @param string|int|null $companies_id
     * @return static
     */
    public function setCompaniesId(string|int|null $companies_id): static
    {
        if ($companies_id and !is_natural($companies_id)) {
            throw new OutOfBoundsException(tr('Specified companies_id ":id" is not numeric', [
                ':id' => $companies_id
            ]));
        }

        return $this->setDataValue('companies_id', get_null(isset_get_typed('integer', $companies_id)));
    }


    /**
     * Returns the companies_id for this object
     *
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        $companies_id = $this->getDataValue('string', 'companies_id');

        if ($companies_id) {
            return new Company($companies_id);
        }

        return null;
    }


    /**
     * Sets the companies_id for this object
     *
     * @param Company|string|int|null $company
     * @return static
     */
    public function setCompany(Company|string|int|null $company): static
    {
        if ($company) {
            if (!is_numeric($company)) {
                $company = Company::get($company);
            }

            if (is_object($company)) {
                $company = $company->getId();
            }
        }

        return $this->setCompaniesId(get_null($company));
    }
}