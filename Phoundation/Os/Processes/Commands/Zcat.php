<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;

/**
 * Class Zcat
 *
 * This class manages the "zcat" command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class Zcat extends Command
{
    use TraitDataFile;

    /**
     * Zcat class constructor
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null                             $operating_system
     * @param string|null                             $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, ?string $operating_system = null, ?string $packages = null)
    {
        parent::__construct($restrictions, $operating_system, $packages);
        $this->setCommand('zcat');
    }


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string
    {
        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }
        $this->addArgument($this->file);

        return parent::getFullCommandLine($background);
    }


    /**
     * Cats the output unzipped to the specified output
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return string|int|bool|array|null
     */
    public function execute(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): string|int|bool|array|null
    {
        return parent::execute($method);
    }
}
