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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getSourceFieldValue('int', 'providers_id');
    }


    /**
     * Sets the providers_id for this object
     *
     * @param int|null $providers_id
     * @return static
     */
    public function setProvidersId(?int $providers_id): static
    {
        return $this->setSourceValue('providers_id', $providers_id);
    }


    /**
     * Returns the providers_id for this user
     *
     * @return Provider|null
     */
    public function getProvider(): ?Provider
    {
        $providers_id = $this->getSourceFieldValue('int', 'providers_id');

        if ($providers_id) {
            return new Provider($providers_id, 'id');
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
        return $this->getSourceFieldValue('string', 'providers_name');
    }


    /**
     * Sets the providers_name for this user
     *
     * @param string|null $providers_name
     * @return static
     */
    public function setProvidersName(?string $providers_name): static
    {
        return $this->setSourceValue('providers_name', $providers_name);
    }
}
