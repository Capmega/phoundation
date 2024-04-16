<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Customers\Customer;

/**
 * Trait TraitDataEntryCustomer
 *
 * This trait contains methods for DataEntry objects that require a customer
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryCustomer
{
    /**
     * Returns the customers_id for this object
     *
     * @return int|null
     */
    public function getCustomersId(): ?int
    {
        return $this->getValueTypesafe('int', 'customers_id');
    }


    /**
     * Sets the customers_id for this object
     *
     * @param int|null $customers_id
     *
     * @return static
     */
    public function setCustomersId(?int $customers_id): static
    {
        return $this->set('customers_id', $customers_id);
    }


    /**
     * Returns the customers_id for this user
     *
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        $customers_id = $this->getValueTypesafe('int', 'customers_id');
        if ($customers_id) {
            return new Customer($customers_id);
        }

        return null;
    }


    /**
     * Returns the customers_name for this user
     *
     * @return string|null
     */
    public function getCustomersName(): ?string
    {
        return $this->getValueTypesafe('string', 'customers_name');
    }


    /**
     * Sets the customers_name for this user
     *
     * @param string|null $customer_name
     *
     * @return static
     */
    public function setCustomersName(?string $customer_name): static
    {
        return $this->set('customers_name', $customer_name);
    }
}
