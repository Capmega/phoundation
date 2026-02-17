<?php

/**
 * Trait TraitDataEntryCustomer
 *
 * This trait contains methods for DataEntry objects that require a customer
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Business\Customers\Customer;
use Phoundation\Business\Customers\Interfaces\CustomerInterface;


trait TraitDataEntryCustomer
{
    /**
     * Setup virtual configuration for Customers
     *
     * @return static
     */
    protected function addVirtualConfigurationCustomers(): static
    {
        return $this->addVirtualConfiguration('customers', Customer::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the customers_id column
     *
     * @return int|null
     */
    public function getCustomersId(): ?int
    {
        return $this->getVirtualData('customers', 'int', 'id');
    }


    /**
     * Sets the customers_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCustomersId(?int $id): static
    {
        return $this->setVirtualData('customers', $id, 'id');
    }


    /**
     * Returns the customers_code column
     *
     * @return string|null
     */
    public function getCustomersCode(): ?string
    {
        return $this->getVirtualData('customers', 'string', 'code');
    }


    /**
     * Sets the customers_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCustomersCode(?string $code): static
    {
        return $this->setVirtualData('customers', $code, 'code');
    }


    /**
     * Returns the customers_name column
     *
     * @return string|null
     */
    public function getCustomersName(): ?string
    {
        return $this->getVirtualData('customers', 'string', 'name');
    }


    /**
     * Sets the customers_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCustomersName(?string $name): static
    {
        return $this->setVirtualData('customers', $name, 'name');
    }


    /**
     * Returns the Customer Object
     *
     * @return CustomerInterface|null
     */
    public function getCustomerObject(): ?CustomerInterface
    {
        return $this->getVirtualObject('customers');
    }


    /**
     * Returns the customers_id for this user
     *
     * @param CustomerInterface|null $_object
     *
     * @return static
     */
    public function setCustomerObject(?CustomerInterface $_object): static
    {
        return $this->setVirtualObject('customers', $_object);
    }
}
