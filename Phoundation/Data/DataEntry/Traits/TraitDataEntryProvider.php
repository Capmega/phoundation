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

use Phoundation\Business\Providers\Interfaces\ProviderInterface;
use Phoundation\Business\Providers\Provider;


trait TraitDataEntryProvider
{
    /**
     * Provider object cache
     *
     * @var ProviderInterface|null $o_provider
     */
    protected ?ProviderInterface $o_provider;


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
        $this->o_provider = null;
        return $this->set($providers_id, 'providers_id');
    }


    /**
     * Returns the provider for this object
     *
     * @return ProviderInterface|null
     */
    public function getProviderObject(): ?ProviderInterface
    {
        if (empty($this->o_provider)) {
            $this->o_provider = Provider::new($this->getTypesafe('int', 'providers_id'))->loadOrNull();
        }

        return $this->o_provider;
    }


    /**
     * Sets the provider for this object
     *
     * @param ProviderInterface|null $o_provider
     * @return TraitDataEntryProvider
     */
    public function setProviderObject(?ProviderInterface $o_provider): static
    {
        $this->setProvidersId($o_provider?->getId());

        $this->o_provider = $o_provider;
        return $this;
    }


    /**
     * Returns the providers_name for this object
     *
     * @return string|null
     */
    public function getProvidersName(): ?string
    {
        return $this->getProviderObject()->getName();
    }


    /**
     * Returns the providers_name for this object
     *
     * @param string|null $providers_name
     *
     * @return static
     */
    public function setProvidersName(?string $providers_name): static
    {
        return $this->setProviderObject(Provider::new(['name' => $providers_name])->loadOrNull());
    }
}
