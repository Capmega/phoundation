<?php

/**
 * Class Configurations
 *
 * This class can manage various user configuration entries.
 *
 * @see       \Phoundation\Accounts\Users\User
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Configuration;

use Phoundation\Accounts\Users\Configuration\Interfaces\ConfigurationsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntry\DataIteratorCore;

class Configurations extends DataIteratorCore implements ConfigurationsInterface
{
    /**
     * Configurations class constructor
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user) {
        $this->setAcceptedDataTypes(static::getDefaultContentDataType());
        $this->setParentObject($user);
    }


    /**
     * Returns a new DataIterator type object
     *
     * @param UserInterface $user
     *
     * @return static
     */
    public static function new(UserInterface $user): static
    {
        return new static($user);
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Configuration::class;
    }
}
