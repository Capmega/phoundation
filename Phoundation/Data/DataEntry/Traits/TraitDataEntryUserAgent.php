<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryUserAgent
 *
 * This trait contains methods for DataEntry objects that require user_agent
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryUserAgent
{
    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->getValueTypesafe('string', 'user_agent');
    }


    /**
     * Sets the ip address for this user
     *
     * @param string|null $user_agent
     *
     * @return static
     */
    public function setUserAgent(?string $user_agent): static
    {
        return $this->set('user_agent', $user_agent);
    }
}
