<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Exception\NotExistsException;
use Phoundation\Processes\Process;


/**
 * Class Repositories
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class RemoteRepositories extends Iterator
{
    use GitProcess {
        __construct as construct;
    }



    /**
     * RemoteRepositories class constructor
     */
    public function __construct(string $path)
    {
        $this->construct($path);
        $this->list = Process::new('git')
            ->setExecutionPath($this->path)
            ->addArgument('remote')
            ->addArgument('show')
            ->executeReturnArray();
    }



    /**
     * Display the repositories on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getList() as $repository) {
            $list[$repository] = [];
        }

        Cli::displayTable($list, ['repository' => tr('Repository')], 'repository');
    }



    /**
     * Returns the specified repository
     *
     * @param string|float|int $key
     * @param bool $exception
     * @return RemoteRepository|null
     */
    public function get(string|float|int $key, bool $exception = false): ?RemoteRepository
    {
        if (!array_key_exists($key, $this->list)) {
            if ($exception) {
                throw new NotExistsException(tr('The repository ":key" does not exist in this object', [
                    ':key' => $key
                ]));
            }
        }

        return RemoteRepository::new($this->path, $key);
    }
}