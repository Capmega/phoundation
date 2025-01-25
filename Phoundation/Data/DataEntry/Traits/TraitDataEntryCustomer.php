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

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Customers\Customer;
use Phoundation\Business\Customers\Interfaces\CustomerInterface;


trait TraitDataEntryCustomer
{
    /**
     * Customer object cache
     *
     * @var CustomerInterface|null $o_customer
     */
    protected ?CustomerInterface $o_customer;


    /**
     * Returns the customers_id for this object
     *
     * @return int|null
     */
    public function getCustomersId(): ?int
    {
        return $this->getTypesafe('int', 'customers_id');
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
        $this->o_customer = null;
        return $this->set($customers_id, 'customers_id');
    }


    /**
     * Returns the customer for this object
     *
     * @return CustomerInterface|null
     */
    public function getCustomerObject(): ?CustomerInterface
    {
        if (empty($this->o_customer)) {
            $this->o_customer = Customer::new($this->getTypesafe('int', 'customers_id'))->loadOrNull();
        }

        return $this->o_customer;
    }


    /**
     * Sets the customer for this object
     *
     * @param CustomerInterface|null $o_customer
     * @return static
     */
    public function setCustomerObject(?CustomerInterface $o_customer): static
    {
        $this->setCustomersId($o_customer?->getId());

        $this->o_customer = $o_customer;
        return $this;
    }


    /**
     * Returns the customers_name for this object
     *
     * @return string|null
     */
    public function getCustomersName(): ?string
    {
        return $this->getCustomerObject()->getName();
    }


    /**
     * Returns the customers_name for this object
     *
     * @param string|null $customers_name
     *
     * @return static
     */
    public function setCustomersName(?string $customers_name): static
    {
        return $this->setCustomerObject(Customer::new(['name' => $customers_name])->loadOrNull());
    }
}
