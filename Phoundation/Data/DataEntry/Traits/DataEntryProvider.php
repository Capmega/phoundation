<?php

declare(strict_types=1);

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
     * @return int|null
     */
    public function getProvidersId(): ?int
    {
        return $this->getDataValue('string', 'providers_id');
    }


    /**
     * Sets the providers_id for this object
     *
     * @param string|int|null $providers_id
     * @return static
     */
    public function setProvidersId(string|int|null $providers_id): static
    {
        if ($providers_id and !is_natural($providers_id)) {
            throw new OutOfBoundsException(tr('Specified providers_id ":id" is not a natural number', [
                ':id' => $providers_id
            ]));
        }

        return $this->setDataValue('providers_id', get_null(isset_get_typed('integer', $providers_id)));
    }

    /**
     * Returns the providers_id for this user
     *
     * @return Provider|null
     */
    public function getProvider(): ?Provider
    {
        $providers_id = $this->getDataValue('string', 'providers_id');

        if ($providers_id) {
            return new Provider($providers_id);
        }

        return null;
    }


    /**
     * Sets the providers_id for this user
     *
     * @param Provider|string|int|null $provider
     * @return static
     */
    public function setProvider(Provider|string|int|null $provider): static
    {
        if ($provider) {
            if (!is_numeric($provider)) {
                $provider = Provider::get($provider);
            }

            if (is_object($provider)) {
                $provider = $provider->getId();
            }
        }

        return $this->setProvidersId(get_null($provider));
    }
}