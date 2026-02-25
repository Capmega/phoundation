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
     * @var UserInterface|null $_user
     */
    protected ?UserInterface $_user = null;


    /**
     * Returns the user
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        return $this->_user;
    }


    /**
     * Sets the user
     *
     * @param UserInterface|null $_user
     *
     * @return static
     */
    public function setUserObject(?UserInterface $_user): static
    {
        $this->_user = get_null($_user);
        return $this;
    }
}
