<?php

/**
 * Class Git
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Versioning\Git\Exception\GitException;
use Phoundation\Developer\Versioning\Git\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemoteRepositoriesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StashInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\TagInterface;
use Phoundation\Developer\Versioning\Versioning;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Stringable;


class Git extends Versioning implements GitInterface
{
    /**
     * The directory that will be checked
     *
     * @var FsDirectoryInterface $directory
     */
    protected FsDirectoryInterface $directory;

    /**
     * The git process
     *
     * @var ProcessInterface $git
     */
    protected ProcessInterface $git;


    /**
     * Git class constructor
     *
     * @param FsDirectoryInterface $directory
     */
    public function __construct(FsDirectoryInterface $directory)
    {
        $this->setDirectory($directory);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param FsDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface $directory): static
    {
        $this->directory = $directory->makeAbsolute()->checkWritable();
        $this->git       = Process::new('git')
                                  ->setExecutionDirectory($this->directory)
                                  ->setTimeout(300);

        return $this;
    }


    /**
     * Clone the specified URL to this directory
     *
     * @return static
     */
    public function clone(string $url): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('clone')
                            ->addArgument($url)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Returns the current git branch for this directory
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranch(string $branch): bool
    {
        return $this->getBranchesObject()->keyExists($branch);
    }


    /**
     * Returns the current git branch for this directory
     *
     * @return string
     */
    public function getBranch(): string
    {
        $output = $this->git->clearArguments()
                            ->addArgument('branch')
                            ->executeReturnArray();

        foreach ($output as $line) {
            if (str_starts_with(trim($line), '*')) {
                return trim(Strings::from($line, '*'));
            }
        }

        throw new GitException(tr('No branch selected for directory ":directory"', [
            ':directory' => $this->directory,
        ]));
    }


    /**
     * Returns the current git branch for this directory
     *
     * @param string $branch
     *
     * @return static
     */
    public function setBranch(string $branch): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('checkout')
                            ->addArgument($branch)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Returns all available git repositories
     *
     * @return RemoteRepositoriesInterface
     */
    public function getRepositoriesObject(): RemoteRepositoriesInterface
    {
        return RemoteRepositories::new()->setDirectory($this->directory);
    }


    /**
     * Generates and returns a new Git object
     *
     * @param FsPathInterface $directory
     *
     * @return static
     */
    public static function new(FsPathInterface $directory): static
    {
        return new static($directory);
    }


    /**
     * Returns a list of available git branches
     *
     * @return BranchesInterface
     */
    public function getBranchesObject(): BranchesInterface
    {
        return new Branches($this->directory);
    }


    /**
     * Stashes the git changes
     *
     * @return StashInterface
     */
    public function getStashObject(): StashInterface
    {
        return Stash::new($this->directory);
    }


    /**
     * Checks out the specified branches or directories for this git directory
     *
     * @param array|Stringable $branches_or_directories
     *
     * @return static
     */
    public function checkout(array|Stringable $branches_or_directories): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('checkout')
                            ->addArguments($branches_or_directories)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Checks out the specified branches or directories for this git directory
     *
     * @param array|string $branches_or_directories
     * @param bool         $files
     * @param bool         $directories
     *
     * @return static
     */
    public function clean(array|string $branches_or_directories, bool $files, bool $directories): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('clean')
                            ->addArgument($files ? '-f' : null)
                            ->addArgument($directories ? '-d' : null)
                            ->addArguments($branches_or_directories)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Resets the current branch to the specified revision
     *
     * @param string                       $revision
     * @param Stringable|array|string|null $files
     *
     * @return static
     */
    public function reset(string $revision, Stringable|array|string|null $files = null): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('reset')
                            ->addArgument($revision)
                            ->addArgument($files)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Apply the specified patch to the specified target file
     *
     * @param array|string|null $files
     *
     * @return static
     */
    public function add(array|string|null $files = null): static
    {
        if (!$files) {
            $files = '.';
        }
        $output = $this->git->clearArguments()
                            ->addArgument('add')
                            ->addArgument($files)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Resets the current branch to the specified revision
     *
     * @param string $message
     * @param bool   $signed
     *
     * @return static
     */
    public function commit(string $message, bool $signed = null): static
    {
        $signed = $signed ?? Config::getBoolean('versioning.git.sign', false);
        $output = $this->git->clearArguments()
                            ->addArgument('commit')
                            ->addArgument('-m')
                            ->addArgument($message)
                            ->addArgument($signed ? '-s' : null)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Returns a Git Tag object to manage git tagging
     *
     * @param string $message
     * @param bool   $signed
     *
     * @return TagInterface
     */
    public function getTagObject(string $message, bool $signed = false): TagInterface
    {
        return new Tag($this->git);
    }


    /**
     * Returns if this git directory has any changes
     *
     * @param FsDirectoryInterface|null $directory
     *
     * @return bool
     */
    public function hasChanges(?FsDirectoryInterface $directory = null): bool
    {
        return (bool) $this->getStatusFilesObject($directory ?? $this->directory)->getCount();
    }


    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param FsPathInterface|null $path
     *
     * @return StatusFilesInterface
     */
    public function getStatusFilesObject(?FsPathInterface $path = null): StatusFilesInterface
    {
        return StatusFiles::new($path ?? $this->directory)->scanChanges();
    }


    /**
     * Save the diff for the specified file to the specified target
     *
     * @note Returns NULL if the specified file has no diff
     *
     *
     * @param array|string $files
     * @param bool         $cached
     *
     * @return FsFileInterface|null
     */
    public function saveDiff(array|string $files, bool $cached = false): ?FsFileInterface
    {
        $diff = $this->getDiff($files, $cached);

        if ($diff) {
            return FsFile::getTemporary(false, sha1(Strings::force($files, '-')) . '.patch', false)
                         ->putContents($diff . PHP_EOL);
        }

        Log::warning(tr('Files ":files" have no diff', [':files' => $files]));

        return null;
    }


    /**
     * Get a diff for the specified file
     *
     * @param array|string|null $files
     * @param bool              $cached
     *
     * @return string
     */
    public function getDiff(array|string|null $files = null, bool $cached = false): string
    {
        return $this->git->clearArguments()
                         ->addArgument('diff')
                         ->addArgument(NOCOLOR ? '--no-color' : null)
                         ->addArgument($cached ? '--cached'   : null)
                         ->addArgument('--')
                         ->addArguments($files)
                         ->executeReturnString();
    }


    /**
     * Get a diff for the specified file
     *
     * @param array|string|null $files
     * @param bool              $cached
     *
     * @return string
     */
    public function getLog(array|string|null $files = null, bool $cached = false): string
    {
        return $this->git->clearArguments()
                         ->addArgument('log')
                         ->addArgument(NOCOLOR ? '--no-color' : null)
                         ->addArgument($cached ? '--cached'   : null)
                         ->addArgument('--')
                         ->addArguments($files)
                         ->executeReturnString();
    }


    /**
     * Apply the specified patch to the specified target file
     *
     * @param FsFileInterface|null $patch_file
     *
     * @return static
     */
    public function apply(?FsFileInterface $patch_file): static
    {
        if (!$patch_file) {
            Log::warning(tr('Ignoring empty patch filename'));

        } else {
            $output = $this->git->clearArguments()
                                ->addArgument('apply')
                                ->addArgument('-v')
                                ->addArgument('--ignore-whitespace')
                                ->addArgument('--ignore-space-change')
                                ->addArgument('--whitespace=nowarn')
                                ->addArgument($patch_file->getSource())
                                ->executeReturnArray();

            Log::notice($output, 1, false);
        }

        return $this;
    }


    /**
     * Push the local changes to the remote repository / branch
     *
     * @param string      $repository
     * @param string|null $branch
     *
     * @return static
     */
    public function push(string $repository, ?string $branch = null): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('push')
                            ->addArguments([
                                $repository,
                                $branch,
                            ])
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @param string $repository
     * @param string $branch
     *
     * @return static
     */
    public function pull(string $repository, string $branch): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('pull')
                            ->addArgument($repository)
                            ->addArgument($branch)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @param string $repository
     *
     * @return static
     */
    public function fetch(string $repository): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('fetch')
                            ->addArgument($repository)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @return static
     */
    public function fetchAll(): static
    {
        $output = $this->git->clearArguments()
                            ->addArguments([
                                'fetch',
                                '--all',
                            ])
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Merge the specified branch into this one
     *
     * @param string $branch
     *
     * @return static
     */
    public function merge(string $branch): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('merge')
                            ->addArgument($branch)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }


    /**
     * Rebase the specified branch into this one
     *
     * @param string $branch
     *
     * @return static
     */
    public function rebase(string $branch): static
    {
        $output = $this->git->clearArguments()
                            ->addArgument('rebase')
                            ->addArgument($branch)
                            ->executeReturnArray();

        Log::notice($output, 1, false);

        return $this;
    }
}
