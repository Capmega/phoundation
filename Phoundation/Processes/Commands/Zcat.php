<?php

declare(strict_types=1);

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Processes\Enum\ExecuteMethod;
use Phoundation\Processes\Enum\Interfaces\ExecuteMethodInterface;


/**
 * Class Zcat
 *
 * This class manages the "zcat" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Zcat extends Command
{
    use DataFile;


    /**
     * Zcat class constructor
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param string|null $packages
     */
    public function __construct(RestrictionsInterface|array|string|null $restrictions = null, ?string $packages = null)
    {
        parent::__construct($restrictions, $packages);
        $this->setInternalCommand('zcat');
    }


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string
    {
        $this->addArgument($this->file);
        return parent::getFullCommandLine($background);
    }


    /**
     * Cats the output unzipped to the specified output
     *
     * @param ExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function execute(ExecuteMethodInterface $method = ExecuteMethod::passthru): string|int|bool|array|null
    {
        return parent::execute($method);
    }
}
