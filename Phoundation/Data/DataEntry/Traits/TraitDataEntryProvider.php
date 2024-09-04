<?php

/**
 * Trait TraitDataEntryProvider
 *
 * This trait contains methods for DataEntry objects that require a provider
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use GeoIp2\ProviderInterface;
use Phoundation\Business\Providers\Provider;


trait TraitDataEntryProvider
{
    /**
     * Returns the providers_id for this object
     *
     * @return int|null
     */
    public function getProvidersId(): ?int
    {
        return $this->getTypesafe('int', 'providers_id');
    }


    /**
     * Sets the providers_id for this object
     *
     * @param int|null $providers_id
     *
     * @return static
     */
    public function setProvidersId(?int $providers_id): static
    {
        return $this->set($providers_id, 'providers_id');
    }


    /**
     * Returns the providers_id for this user
     *
     * @return ProviderInterface|null
     */
    public function getProvider(): ?ProviderInterface
    {
        $providers_id = $this->getTypesafe('int', 'providers_id');

        if ($providers_id) {
            return new Provider($providers_id);
        }

        return null;
    }


    /**
     * Returns the providers_name for this user
     *
     * @return string|null
     */
    public function getProvidersName(): ?string
    {
        return $this->getTypesafe('string', 'providers_name');
    }


    /**
     * Sets the providers_name for this user
     *
     * @param string|null $providers_name
     *
     * @return static
     */
    public function setProvidersName(?string $providers_name): static
    {
        return $this->set($providers_name, 'providers_name');
    }
}
