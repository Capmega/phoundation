<?php

namespace Phoundation\Core\Modes\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;


interface ModeInterface
{
    /**
     * Returns the mode
     *
     * @return string
     */
    public function getMode(): string;


    /**
     * Returns the user that set this mode
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface;


    /**
     * Returns the date/time when this mode was set
     *
     * @return PhoDateTimeInterface|null
     */
    public function getDateTime(): ?PhoDateTimeInterface;
}
