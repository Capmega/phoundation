<?php

/**
 * Trait TraitDataOsUser
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Core\Core;

trait TraitDataOsUser
{
    /**
     * The user for this object
     *
     * @var string|null $os_user
     */
    protected ?string $os_user = null;


    /**
     * Returns the operating system  user
     *
     * @return string|null
     */
    public function getOsUser(): ?string
    {
        if (empty($this->os_user)) {
            $this->detectOsUser();
        }

        return $this->os_user;
    }


    /**
     * Sets the operating system user
     *
     * @param string|null $os_user
     *
     * @return static
     */
    public function setOsUser(?string $os_user): static
    {
        $this->os_user = get_null($os_user);
        return $this;
    }


    /**
     * Detects the operating system user
     *
     * @return static
     */
    public function detectOsUser(): static
    {
        $this->os_user = Core::getProcessUsername();
        return $this;
    }
}
