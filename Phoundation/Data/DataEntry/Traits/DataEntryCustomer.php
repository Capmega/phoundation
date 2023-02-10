<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Customers\Customer;


/**
 * Trait DataEntryCustomer
 *
 * This trait contains methods for DataEntry objects that require a customer
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCustomer
{
    /**
     * Returns the customers_id for this object
     *
     * @return string|null
     */
    public function getCustomersId(): ?string
    {
        return $this->getDataValue('customers_id');
    }



    /**
     * Sets the customers_id for this object
     *
     * @param string|null $customers_id
     * @return static
     */
    public function setCustomersId(?string $customers_id): static
    {
        return $this->setDataValue('customers_id', $customers_id);
    }



    /**
     * Returns the customers_id for this user
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        $customers_id = $this->getDataValue('customers_id');

        if ($customers_id) {
            return new Customer($customers_id);
        }

        return null;
    }



    /**
     * Sets the customers_id for this user
     *
     * @param Customer|string|int|null $customers_id
     * @return static
     */
    public function setCustomer(Customer|string|int|null $customers_id): static
    {
        if (!is_numeric($customers_id)) {
            $customers_id = Customer::get($customers_id);
        }

        if (is_object($customers_id)) {
            $customers_id = $customers_id->getId();
        }

        return $this->setDataValue('customers_id', $customers_id);
    }
}