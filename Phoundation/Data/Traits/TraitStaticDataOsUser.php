<?php

/**
 * Trait TraitStaticDataOsUser
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


use Phoundation\Core\Core;

trait TraitStaticDataOsUser
{
    /**
     * The user for this object
     *
     * @var string|null $os_user
     */
    protected static ?string $os_user = null;


    /**
     * Returns the operating system  user
     *
     * @return string|null
     */
    public static function getOsUser(): ?string
    {
        if (empty(static::$os_user)) {
            static::detectOsUser();
        }

        return static::$os_user;
    }


    /**
     * Sets the operating system user
     *
     * @param string|null $os_user
     *
     * @return void
     */
    public static function setOsUser(?string $os_user): void
    {
        static::$os_user = get_null($os_user);
    }


    /**
     * Detects the operating system user
     *
     * @return void
     */
    public static function detectOsUser(): void
    {
        static::$os_user = Core::getProcessUsername();
    }
}
