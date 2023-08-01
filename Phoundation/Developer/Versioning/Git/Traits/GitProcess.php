<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Processes\Process;


/**
 * Trait GitProcess
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
trait GitProcess
{
    /**
     * The path that will be checked
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The git process
     *
     * @var Process $git_process
     */
    protected Process $git_process;


    /**
     * GitPath class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
    }


    /**
     * Returns a new GitPath object
     *
     * @param string $path
     * @return static
     */
    public static function new(string $path): static
    {
        return new static($path);
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string $path
     * @return static
     */
    public function setPath(string $path): static
    {
        $this->path        = Filesystem::absolute($path);
        $this->git_process = Process::new('git')->setExecutionPath($this->path);

        if (!$this->path) {
            if (!file_exists($path)) {
                throw new OutOfBoundsException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }
        }

        return $this;
    }
}