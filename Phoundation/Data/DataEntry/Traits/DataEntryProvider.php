<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Providers\Provider;


/**
 * Trait DataEntryProvider
 *
 * This trait contains methods for DataEntry objects that require a provider
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryProvider
{
    /**
     * Returns the providers_id for this object
     *
     * @return string|null
     */
    public function getProvidersId(): ?string
    {
        return $this->getDataValue('providers_id');
    }



    /**
     * Sets the providers_id for this object
     *
     * @param string|null $providers_id
     * @return static
     */
    public function setProvidersId(?string $providers_id): static
    {
        return $this->setDataValue('providers_id', $providers_id);
    }



    /**
     * Returns the providers_id for this user
     *
     * @return Provider|null
     */
    public function getProvider(): ?Provider
    {
        $providers_id = $this->getDataValue('providers_id');

        if ($providers_id) {
            return new Provider($providers_id);
        }

        return null;
    }



    /**
     * Sets the providers_id for this user
     *
     * @param Provider|string|int|null $providers_id
     * @return static
     */
    public function setProvider(Provider|string|int|null $providers_id): static
    {
        if (!is_numeric($providers_id)) {
            $providers_id = Provider::get($providers_id);
        }

        if (is_object($providers_id)) {
            $providers_id = $providers_id->getId();
        }

        return $this->setDataValue('providers_id', $providers_id);
    }
}