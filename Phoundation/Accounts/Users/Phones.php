<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\PhonesInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Phones
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Phones extends DataList implements PhonesInterface
{
    /**
     * Users class constructor
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `accounts_phones`.`id`,
                                        `accounts_phones`.`phone`,
                                        `accounts_phones`.`type`
                               FROM     `accounts_phones`
                               WHERE    `accounts_phones`.`users_id` = :users_id
                                 AND    `accounts_phones`.`status` IS NULL
                               ORDER BY `phone`');

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_phones';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Phone::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'phone';
    }


    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     * @return static
     */
    public function setParent(DataEntryInterface $parent): static
    {
        if ($parent instanceof UserInterface) {
            // Clear the source to avoid having a parent with the wrong children
            $this->source = [];
            return $this->setParentTrait($parent);
        }

        throw new OutOfBoundsException(tr('Specified parent ":parent" is invalid, it must have a UserInterface interface', [
            ':parent' => $parent
        ]));
    }


    /**
     * Returns Phones list object with phones for the specified user.
     *
     * @return static
     * @throws SqlMultipleResultsException, NotExistsException
     */
    public function load(): static
    {
        $this->parent  = User::get($this->parent, 'seo_name');
        $this->execute = [':users_id' => $this->parent->getId()];

        return parent::load();
    }
}
