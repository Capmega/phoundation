<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataUser
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataUser
{
    /**
     * The user for this object
     *
     * @var string|null $user
     */
    protected ?string $user = null;


    /**
     * Returns the user
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }


    /**
     * Sets the user
     *
     * @param string|null $user
     *
     * @return static
     */
    public function setUser(?string $user): static
    {
        $this->user = get_null($user);
        return $this;
    }
}
