<?php

/**
 * Trait TraitDataUserObject
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


use Phoundation\Accounts\Users\Interfaces\UserInterface;

trait TraitDataUserObject
{
    /**
     * The user for this object
     *
     * @var UserInterface|null $o_user
     */
    protected ?UserInterface $o_user = null;


    /**
     * Returns the user
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        return $this->o_user;
    }


    /**
     * Sets the user
     *
     * @param UserInterface|null $o_user
     *
     * @return static
     */
    public function setUserObject(?UserInterface $o_user): static
    {
        $this->o_user = get_null($o_user);
        return $this;
    }
}
