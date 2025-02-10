<?php

/**
 * Trait TraitDataEntryProvider
 *
 * This trait contains methods for DataEntry objects that require a provider
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Business\Providers\Interfaces\ProviderInterface;
use Phoundation\Business\Providers\Provider;


trait TraitDataEntryProvider
{
    /**
     * Setup virtual configuration for Providers
     *
     * @return static
     */
    protected function addVirtualConfigurationProviders(): static
    {
        return $this->addVirtualConfiguration('providers', Provider::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the providers_id column
     *
     * @return int|null
     */
    public function getProvidersId(): ?int
    {
        return $this->getVirtualData('providers', 'int', 'id');
    }


    /**
     * Sets the providers_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setProvidersId(?int $id): static
    {
        return $this->setVirtualData('providers', $id, 'id');
    }


    /**
     * Returns the providers_code column
     *
     * @return string|null
     */
    public function getProvidersCode(): ?string
    {
        return $this->getVirtualData('providers', 'string', 'code');
    }


    /**
     * Sets the providers_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setProvidersCode(?string $code): static
    {
        return $this->setVirtualData('providers', $code, 'code');
    }


    /**
     * Returns the providers_name column
     *
     * @return string|null
     */
    public function getProvidersName(): ?string
    {
        return $this->getVirtualData('providers', 'string', 'name');
    }


    /**
     * Sets the providers_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setProvidersName(?string $name): static
    {
        return $this->setVirtualData('providers', $name, 'name');
    }


    /**
     * Returns the Provider Object
     *
     * @return ProviderInterface|null
     */
    public function getProviderObject(): ?ProviderInterface
    {
        return $this->getVirtualObject('providers');
    }


    /**
     * Returns the providers_id for this user
     *
     * @param ProviderInterface|null $o_object
     *
     * @return static
     */
    public function setProviderObject(?ProviderInterface $o_object): static
    {
        return $this->setVirtualObject('providers', $o_object);
    }
}
