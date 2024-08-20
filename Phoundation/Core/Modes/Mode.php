<?php

/**
 * Class Mode
 *
 * This class tracks Core modes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Modes;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\SystemUser;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Modes\Interfaces\ModeInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;


class Mode implements ModeInterface
{
    /**
     * The mode being tracked
     *
     * @var string $mode
     */
    protected string $mode;

    /**
     * The user who enabled this mode
     *
     * @var UserInterface|null $user
     */
    protected ?UserInterface $user;

    /**
     * The date/time when this mode was enabled
     *
     * @var DateTimeInterface|null $datetime
     */
    protected ?DateTimeInterface $datetime;


    /**
     * Mode class constructor
     *
     * @param string               $mode
     * @param FsFileInterface|null $mode_file
     */
    public function __construct(string $mode, ?FsFileInterface $mode_file = null)
    {
        $this->mode     = $mode;
        $this->datetime = $mode_file?->getMtime();

        try {
            if ($mode_file) {
                $this->user = User::new(['email' => $mode_file->getBasename()]);
            }

        } catch (DataEntryNotExistsException) {
            $this->user = new SystemUser();
        }
    }


    /**
     * Returns the mode
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }


    /**
     * Returns the user that set this mode
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        return $this->user;
    }


    /**
     * Returns the date/time when this mode was set
     *
     * @return DateTimeInterface|null
     */
    public function getDateTime(): ?DateTimeInterface
    {
        return $this->datetime;
    }
}
