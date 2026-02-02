<?php

/**
 * Class Pho
 *
 * This class is used to easily execute Phoundation commands
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoDirectory;


class Pho extends PhoCore
{
    /**
     * Pho class constructor.
     *
     * @param array|string|null     $commands
     * @param PhoFileInterface|null $pho
     */
    public function __construct(array|string|null $commands = null, ?PhoFileInterface $pho = null)
    {
        $this->init($commands, $pho);
    }


    /**
     * Create a new process factory for a specific Phoundation command
     *
     * @param array|string|null     $commands
     * @param PhoFileInterface|null $pho
     *
     * @return static
     */
    public static function new(array|string|null $commands = null, ?PhoFileInterface $pho = null): static
    {
        return new static($commands, $pho);
    }
}
