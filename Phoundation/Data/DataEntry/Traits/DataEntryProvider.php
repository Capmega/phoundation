<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Providers\Provider;
use Phoundation\Exception\OutOfBoundsException;

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
        return get_null((integer) $this->getDataValue('providers_id'));
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

        return $this->setDataValue('providers_id', (integer) $providers_id);
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