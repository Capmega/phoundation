<?php

/**
 * Class Repositories
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Repositories;

use Phoundation\Cli\Cli;
use Phoundation\Data\DataEntries\DataIteratorCore;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Versioning\Git\Interfaces\RemoteRepositoriesInterface;
use Phoundation\Developer\Versioning\Git\RemoteRepository;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Exception\NotExistsException;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Process;
use ReturnTypeWillChange;
use Stringable;


class Repositories extends DataIteratorCore implements RepositoriesInterface
{
    use TraitGitProcess {
        __construct as construct;
    }


    /**
     * RemoteRepositories class constructor
     *
     * @param PhoPathInterface|null $o_parent_path
     */
    public function __construct(?PhoPathInterface $o_parent_path = null)
    {
        $this->construct($o_parent_path);
        $this->source = Process::new('git')
                               ->setExecutionDirectory($this->o_path)
                               ->addArgument('remote')
                               ->addArgument('show')
                               ->executeReturnArray();
    }


    /**
     * Scans for repositories on the current machine and registers them in the database
     *
     * @param PhoPathInterface|null $path
     * @param bool                  $delete_gone
     *
     * @return static
     */
    public function scan(?PhoPathInterface $path = null, bool $delete_gone = true): static
    {
        $this->load();

        $found_repositories = Find::new()
                                  ->setPathObject($path)
                                  ->setType('d')
                                  ->setName('.git');

        foreach ($found_repositories as $repository) {

        }
    }
}
