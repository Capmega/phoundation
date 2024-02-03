<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Exception\Interfaces;


/**
 * Class AuthenticationException
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface AuthenticationExceptionInterface
{
    /**
     * Returns the new target
     *
     * @return string|int|null
     */
    public function getNewTarget(): string|int|null;

    /**
     * Sets the new target
     *
     * @param string|int|null $new_target
     * @return static
     */
    public function setNewTarget(string|int|null $new_target): static;
}
