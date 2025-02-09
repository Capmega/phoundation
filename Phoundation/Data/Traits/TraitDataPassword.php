<?php

/**
 * Trait TraitDataPassword
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataPassword
{
    /**
     * The password for this object
     *
     * @var string|null $password
     */
    protected ?string $password = null;


    /**
     * Returns the pass for this object
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }


    /**
     * Sets the pass for this object
     *
     * @param string|null $pass
     *
     * @return static
     */
    public function setPassword(?string $pass): static
    {
        $this->password = get_null($pass);
        return $this;
    }
}
