<?php

/**
 * Trait TraitDataAuthentication
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


use Phoundation\Accounts\Users\Interfaces\AuthenticationInterface;


trait TraitDataAuthentication
{
    /**
     * @var AuthenticationInterface|null $authentication
     */
    protected ?AuthenticationInterface $authentication = null;


    /**
     * Returns the authentication object
     *
     * @return AuthenticationInterface|null
     */
    public function getAuthentication(): ?AuthenticationInterface
    {
        return $this->authentication;
    }


    /**
     * Sets the authentication object
     *
     * @param AuthenticationInterface|null $authentication
     *
     * @return static
     */
    public function setAuthentication(?AuthenticationInterface $authentication): static
    {
        $this->authentication = $authentication;

        return $this;
    }
}
