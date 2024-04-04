<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Data\Traits\TraitNewSource;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\Process;


/**
 * Trait TraitGitProcess
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
trait TraitGitProcess
{
    use TraitNewSource;


    /**
     * The directory that will be checked
     *
     * @var string $directory
     */
    protected string $directory;

    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }


    /**
     * The git process
     *
     * @var ProcessInterface $git_process
     */
    protected ProcessInterface $git_process;


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param string $directory
     *
     * @return static
     */
    public function setDirectory(string $directory): static
    {
        $this->directory   = Path::getAbsolute($directory);
        $this->git_process = Process::new('git')->setExecutionDirectory($this->directory);

        if (!$this->directory) {
            if (!file_exists($directory)) {
                throw new OutOfBoundsException(tr('The specified directory ":directory" does not exist', [
                    ':directory' => $directory,
                ]));
            }
        }

        return $this;
    }
}
