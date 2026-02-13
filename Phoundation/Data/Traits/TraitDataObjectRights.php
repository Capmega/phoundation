<?php

/**
 * Trait TraitDataObjectRights
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

use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;


trait TraitDataObjectRights
{
    /**
     * The rights for this user
     *
     * @var RightsInterface|null $_rights
     */
    protected ?RightsInterface $_rights = null;


    /**
     * Returns the roles for this user
     *
     * @param bool $reload
     * @param bool $order
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $reload = false, bool $order = false): RightsInterface
    {
        if ($this instanceof DataEntryInterface) {
            if ($this->isNew()) {
                throw new AccountsException(tr('Cannot access rights for user ":user", the user has not yet been saved', [
                    ':user' => $this->getLogId(),
                ]));
            }
        }

        if (empty($this->_rights) or $reload) {
            $this->_rights = Rights::new()
                                    ->setParentObject($this)
                                    ->load($order ? ['$order' => ['right' => 'asc']] : null);
        }

        return $this->_rights;
    }


    /**
     * Sets the rights object
     *
     * @param RightsInterface|null $_rights
     *
     * @return static
     */
    protected function setRightsObject(RightsInterface|null $_rights): static
    {
        $this->_rights = $_rights;
        return $this;
    }


    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasSomeRights(array|string $rights, ?string $always_match = 'god'): bool
    {
        return $this->getRightsObject()->hasSome($rights, $always_match);
    }


    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasAllRights(array|string $rights, ?string $always_match = 'god'): bool
    {
        return $this->getRightsObject()->hasAll($rights, $always_match);
    }


    /**
     * Adds the specified right to the list
     *
     * @param RightInterface|string|null $_right
     *
     * @return static
     */
    public function addRight(RightInterface|string|null $_right): static
    {
        $this->getRightsObject()->add(Right::new()->loadNull($_right));
        return $this;
    }


    /**
     * Removes the specified right from the list
     *
     * @param RightInterface|string|null $_right
     *
     * @return static
     */
    public function removeRight(RightInterface|string|null $_right): static
    {
        if ($_right instanceof RightInterface) {
            $_right = $_right->getName();
        }

        $this->getRightsObject()->removeKeys($_right);

        return $this;
    }
}
