<?php

namespace Phoundation\Data\Traits;

use Phoundation\Seo\Seo;



/**
 * Trait DataUserPass
 *
 *  
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataUserPass
{
    /**
     * The user for this object
     *
     * @var string|null $user
     */
    protected ?string $user = null;

    /**
     * The password for this object
     *
     * @var string|null $password
     */
    protected ?string $password = null;



    /**
     * Returns the user for this object
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }



    /**
     * Sets the user for this object
     *
     * @param string|null $user
     * @return static
     */
    public function setUser(?string $user): static
    {
        $this->user = $user;
        return $this;
    }



    /**
     * Returns the pass for this object
     *
     * @return string|null
     */
    public function getPass(): ?string
    {
        return $this->pass;
    }



    /**
     * Sets the pass for this object
     *
     * @param string|null $pass
     * @return static
     */
    public function setPass(?string $pass): static
    {
        $this->pass = $pass;
        return $this;
    }
}