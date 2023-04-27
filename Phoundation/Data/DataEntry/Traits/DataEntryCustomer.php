<?php

declare(strict_types=1);

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
     * @return int|null
     */
    public function getCustomersId(): ?int
    {
        return $this->getDataValue('string', 'customers_id');
    }


    /**
     * Sets the customers_id for this object
     *
     * @param string|int|null $customers_id
     * @return static
     */
    public function setCustomersId(string|int|null $customers_id): static
    {
        if ($customers_id and !is_natural($customers_id)) {
            throw new OutOfBoundsException(tr('Specified customers_id ":id" is not numeric', [
                ':id' => $customers_id
            ]));
        }

        return $this->setDataValue('customers_id', get_null(isset_get_typed('integer', $customers_id)));
    }

    /**
     * Returns the customers_id for this user
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        $customers_id = $this->getDataValue('string', 'customers_id');

        if ($customers_id) {
            return new Customer($customers_id);
        }

        return null;
    }


    /**
     * Sets the customers_id for this user
     *
     * @param Customer|string|int|null $customer
     * @return static
     */
    public function setCustomer(Customer|string|int|null $customer): static
    {
        if ($customer) {
            if (!is_numeric($customer)) {
                $customer = Customer::get($customer);
            }

            if (is_object($customer)) {
                $customer = $customer->getId();
            }
        }

        return $this->setCustomersId(get_null($customer));
    }
}