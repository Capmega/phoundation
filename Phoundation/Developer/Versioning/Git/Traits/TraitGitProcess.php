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
     * @var PhoPathInterface|null $_path
     */
    protected ?PhoPathInterface $_path;

    /**
     * The git process
     *
     * @var ProcessInterface|null $_git_process
     */
    protected ?ProcessInterface $_git_process;


    /**
     * TraitGitProcess trait constructor
     *
     * @param PhoPathInterface|null $_parent_path
     */
    public function __construct(?PhoPathInterface $_parent_path = null)
    {
        $this->setPath($_parent_path);
    }


    /**
     * Returns a new static object that accepts $directory in the constructor
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public static function new(?PhoPathInterface $_path = null): static
    {
        return new static($_path);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return PhoPathInterface
     */
    public function getPath(): PhoPathInterface
    {
        return $this->_path;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPath(?PhoPathInterface $_path): static
    {
        if ($_path) {
            $this->_path        = $_path->makeAbsolute()->checkReadable();
            $this->_git_process = Process::new('git')->setExecutionDirectory($this->_path->getDirectoryObject());

        } else {
            $this->_path        = null;
            $this->_git_process = null;
        }

        return $this;
    }
}
