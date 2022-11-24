<?php

namespace Phoundation\Accounts;

use Iterator;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataList;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Roles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Roles implements Iterator
{
    use DataList;



    /**
     * The user for this roles list
     *
     * @var User
     */
    protected User $user;

    /**
     * The roles list
     *
     * @var array $roles
     */
    protected array $roles;



    /**
     * @param User|int|null $user
     */
    public function __construct(User|int|null $user) {
        if (!is_object($user)) {
            $user = User::new($user);
        }

        $this->user = $user;
    }



    /**
     * Returns new Roles object
     *
     * @param User|int|null $user
     * @return static
     */
    public static function new(User|int|null $user): static
    {
        return new static($user);
    }



    /**
     * Load the data for this roles list
     *
     * @return $this
     */
    public function load(): static
    {
        if (!$this->user) {
            throw new OutOfBoundsException(tr('Cannot load roles for user, no user specified'));
        }

        return $this;
    }



    /**
     * Save this roles list
     *
     * @return $this
     */
    public function save(): static
    {

        return $this;
    }




    /**
     * Returns the current role
     *
     * @return mixed
     */
    public function current(): mixed
    {
        // TODO: Implement current() method.
    }



    /**
     * Returns the current role
     *
     * @return mixed
     */
    public function next(): void
    {
        // TODO: Implement next() method.
    }



    /**
     * Returns the current role
     *
     * @return mixed
     */
    public function key(): mixed
    {
        // TODO: Implement key() method.
    }



    /**
     * Returns the current role
     *
     * @return mixed
     */
    public function valid(): bool
    {
        // TODO: Implement valid() method.
    }



    /**
     * Returns the current role
     *
     * @return mixed
     */
    public function rewind(): void
    {
        // TODO: Implement rewind() method.
    }
}