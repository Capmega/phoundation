<?php

/**
 * Trait TraitGitProcess
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\Process;


trait TraitGitProcess
{
    /**
     * The directory that will be checked
     *
     * @var PhoPathInterface|null $o_path
     */
    protected ?PhoPathInterface $o_path;

    /**
     * The git process
     *
     * @var ProcessInterface|null $o_git_process
     */
    protected ?ProcessInterface $o_git_process;


    /**
     * TraitGitProcess trait constructor
     *
     * @param PhoPathInterface|null $o_parent_path
     */
    public function __construct(?PhoPathInterface $o_parent_path = null)
    {
        $this->setPath($o_parent_path);
    }


    /**
     * Returns a new static object that accepts $directory in the constructor
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $o_path = null): static
    {
        return new static($o_path);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return PhoPathInterface
     */
    public function getPath(): PhoPathInterface
    {
        return $this->o_path;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public function setPath(?PhoPathInterface $o_path): static
    {
        if ($o_path) {
            $this->o_path        = $o_path->makeAbsolute()->checkReadable();
            $this->o_git_process = Process::new('git')->setExecutionDirectory($this->o_path->getDirectoryObject());

        } else {
            $this->o_path        = null;
            $this->o_git_process = null;
        }

        return $this;
    }
}
