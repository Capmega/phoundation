<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataEmail
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEmail
{
    /**
     *
     *
     * @var string|null $email
     */
    protected ?string $email = null;


    /**
     * Returns the email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }


    /**
     * Sets the email
     *
     * @param string|null $email
     * @return static
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }
}