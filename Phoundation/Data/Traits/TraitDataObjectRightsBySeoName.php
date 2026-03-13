<?php

/**
 * Trait TraitDataObjectRightsBySeoName
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
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\RightsBySeoName;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;


trait TraitDataObjectRightsBySeoName
{
    use TraitDataObjectRights;


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
        if (($this instanceof DataEntryInterface) and $this->isNew()) {
            throw new AccountsException(tr('Cannot access rights for user ":user", the user has not yet been saved', [
                ':user' => $this->getLogId(),
            ]));
        }

        if (empty($this->_rights) or $reload) {
            $this->_rights = RightsBySeoName::new()
                                             ->setParentObject($this)
                                             ->load($order ? ['$order' => ['right' => 'asc']] : null);
        }

        return $this->_rights;
    }
}
