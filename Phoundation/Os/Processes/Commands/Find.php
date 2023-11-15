<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Filesystem\Files;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Stringable;


/**
 * Class Find
 *
 * This class manages the "find" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Find extends Command implements FindInterface
{
    /**
     * The path in which to find
     *
     * @var string
     */
    protected string $path;


    /**
     * Returns the path in which to find
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * Sets the path in which to find
     *
     * @param Stringable|string $path
     * @return $this
     */
    public function setPath(Stringable|string $path): static
    {
        $this->path = (string) $path;
        return $this;
    }


    /**
     * Returns a Files-object containing the found files
     *
     * @return FilesInterface
     */
    public function find(): FilesInterface
    {
        $output = $this
            ->setInternalCommand('find')
            ->setTimeout(10)
            ->executeReturnArray();

        return Files::new()->setSource($output);
    }
}
