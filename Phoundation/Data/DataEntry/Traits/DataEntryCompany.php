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
     * @return int|null
     */
    public function getCompaniesId(): ?int
    {
        return get_null((integer) $this->getDataValue('companies_id'));
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

        return $this->setDataValue('companies_id', (integer) $companies_id);
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