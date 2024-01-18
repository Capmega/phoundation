<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Tracks if each directory's contents before the directory itself.  The -delete action also implies
     *
     * @var bool|null $follow_symlinks
     */
    protected ?bool $follow_symlinks = false;

    /**
     * Tracks
     *
     * @var bool $depth
     */
    protected bool $depth = false;


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
            ->setCommand('find')
            ->setTimeout(10)
            ->executeReturnArray();

        return Files::new()->setSource($output);
    }
}
