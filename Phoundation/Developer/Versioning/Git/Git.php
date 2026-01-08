<?php

/**
 * Class Git
 *
 * Driver class for the git versioning system
 *
 * This class contains (most of the basic) methods to manage all basic operations one would do with git. The constructor requires a PhoDirectory object
 * containing the directory of the git repository on which this class will be working
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Versioning\Git\Exception\GitException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Versioning;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;


class Git extends Versioning implements GitInterface
{
    /**
     * The directory that will be checked
     *
     * @var PhoDirectoryInterface $o_directory
     */
    protected PhoDirectoryInterface $o_directory;

    /**
     * The git process
     *
     * @var ProcessInterface $o_process
     */
    protected ProcessInterface $o_process;


    /**
     * Git class constructor
     *
     * @param PhoDirectoryInterface $o_directory
     */
    public function __construct(PhoDirectoryInterface $o_directory)
    {
        $this->setDirectoryObject($o_directory);
    }


    /**
     * Generates and returns a new Git object
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return static
     */
    public static function new(PhoDirectoryInterface $o_directory): static
    {
        return new static($o_directory);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(): PhoDirectoryInterface
    {
        return $this->o_directory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface $o_directory): static
    {
        $this->o_directory = $o_directory->makeAbsolute()->checkReadable();
        $this->o_process   = Process::new('git')
                                    ->setExecutionDirectory($this->o_directory)
                                    ->setTimeout(300);

        return $this;
    }


    /**
     * Clone the specified URL to this directory
     *
     * @param string $url
     *
     * @return static
     */
    public function clone(string $url): static
    {
        $output = $this->o_process->clearArguments()
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
        return array_key_exists($branch, $this->getBranches());
    }


    /**
     * Returns the current git branch for this directory
     *
     * @return string
     */
    public function getCurrentBranch(): string
    {
        $output = $this->o_process->clearArguments()
                                  ->addArgument('branch')
                                  ->executeReturnArray();

        foreach ($output as $line) {
            if (str_starts_with(trim($line), '*')) {
                return trim(Strings::from($line, '*'));
            }
        }

        throw new GitException(tr('No branch selected for directory ":directory"', [
            ':directory' => $this->o_directory,
        ]));
    }


    /**
     * Sets the current git branch for this directory
     *
     * @param string $branch
     *
     * @return static
     */
    public function setCurrentBranch(string $branch): static
    {
        $output = $this->o_process->clearArguments()
                                  ->addArgument('checkout')
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Creates the specified GIT branch for this directory
     *
     * @param string $branch
     * @param bool   $reset
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false): static
    {
        $output = $this->o_process->clearArguments()
                                  ->addArguments(['checkout', ($reset ? '-B' : '-b')])
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch
     * @param bool   $force
     *
     * @return static
     */
    public function deleteBranch(string $branch, bool $force = false): static
    {
        $output = $this->o_process->clearArguments()
                                  ->addArguments(['branch', '-d', ($force or FORCE ? '-f' : null)])
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch
     * @param string $remote
     *
     * @return static
     */
    public function deleteBranchRemote(string $branch, string $remote): static
    {
        $this->checkRemote($remote);

        $output = $this->o_process->clearArguments()
                                  ->addArguments(['push', $remote, $branch])
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Returns a list of available git branches
     *
     * @return array
     */
    public function getBranches(): array
    {
        $source  = [];
        $results = $this->o_process
                        ->clearArguments()
                        ->addArgument('branch')
                        ->addArgument('--quiet')
                        ->addArgument(ALL ? '-a' : null)
                        ->addArgument('--no-color')
                        ->executeReturnArray();

        foreach ($results as $line) {
            if (str_starts_with($line, '*')) {
                $source[substr($line, 2)] = true;

            } else {
                $source[substr($line, 2)] = false;
            }
        }

        return $source;
    }


    /**
     * Returns a list of available git tags
     *
     * @return array
     */
    public function getTags(): array
    {
        $return = $this->o_process
                       ->clearArguments()
                       ->addArgument('tag')
                       ->addArgument('-l')
                       ->executeReturnArray();

        Log::notice($return, 1, false);
        return Arrays::valueToKeys($return);
    }


    /**
     * Stashes the changes in the current repository
     *
     * @param PhoPathInterface|array|string|null $o_paths
     *
     * @return static
     */
    public function stash(PhoPathInterface|array|string|null $o_paths = null): static
    {
        $output = $this->o_process
                       ->clearArguments()
                       ->addArgument('stash')
                       ->addArgument('--')
                       ->addArguments($o_paths)
                       ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Pops the last changes from the git stash stashes over the working tree
     *
     * @return static
     */
    public function stashPop(): static
    {
        $output = $this->o_process
                       ->clearArguments()
                       ->addArgument('stash')
                       ->addArgument('pop')
                       ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Returns an array containing all the changes in the last available git stash
     *
     * @return array
     */
    public function stashShow(): array
    {
        return $this->o_process
                    ->clearArguments()
                    ->addArgument('stash')
                    ->addArgument('show')
                    ->executeReturnArray();
    }


    /**
     * Returns a list of git stashes
     *
     * @return array
     */
    public function getStashList(): array
    {
        $return  = [];
        $results = $this->o_process
                        ->clearArguments()
                        ->addArgument('stash')
                        ->addArgument('list')
                        ->executeReturnArray();

        foreach ($results as $result) {
            preg_match_all('/stash@\{(\d+)\}:\s(.+)/', $result, $matches);
            $return[$matches[0][0]] = $matches[2][0];
        }

        return $return;
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
        $output = $this->o_process->clearArguments()
                                  ->addArgument('checkout')
                                  ->addArguments($branches_or_directories)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Returns the remotes available for this git repository
     *
     * @return array
     */
    public function getRemotes(): array
    {
        $return = $this->o_process->clearArguments()
                                  ->addArgument('remote')
                                  ->executeReturnArray();

        Log::notice($return, 1, false);
        return Arrays::valueToKeys($return);
    }


    /**
     * Returns true if the specified remote exists for this repository
     *
     * @param string $remote
     *
     * @return bool
     */
    public function hasRemote(string $remote): bool
    {
        return array_key_exists($remote, $this->getRemotes());
    }


    /**
     * Throws an exception if the specified remote does not exist for this GIT repository
     *
     * @param string $remote
     *
     * @return static
     */
    public function checkRemote(string $remote): static
    {
        if ($this->hasRemote($remote)) {
            return $this;
        }

        throw new GitException(ts('The specified remote ":remote" does not exist for the GIT repository ":repository"', [
            ':remote'     => $remote,
            ':repository' => $this->o_directory
        ]));
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
        $output = $this->o_process->clearArguments()
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
        $output = $this->o_process->clearArguments()
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
        $output = $this->o_process->clearArguments()
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
    public function commit(string $message, ?bool $signed = null): static
    {
        $signed = $signed ?? config()->getBoolean('versioning.git.sign', false);
        $output = $this->o_process->clearArguments()
                                  ->addArgument('commit')
                                  ->addArgument('-m')
                                  ->addArgument($message)
                                  ->addArgument($signed ? '-s' : null)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Returns if this git directory has any changes
     *
     * @param PhoDirectoryInterface|null $directory
     *
     * @return bool
     */
    public function hasChanges(?PhoDirectoryInterface $directory = null): bool
    {
        return (bool) $this->getStatusFilesObject($directory ?? $this->o_directory)->getCount();
    }


    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param PhoPathInterface|null $path
     *
     * @return StatusFilesInterface
     */
    public function getStatusFilesObject(?PhoPathInterface $path = null): StatusFilesInterface
    {
        return StatusFiles::new($path ?? $this->o_directory)->scanChanges();
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
     * @return PhoFileInterface|null
     */
    public function saveDiff(array|string $files, bool $cached = false): ?PhoFileInterface
    {
        $diff = $this->getDiff($files, $cached);

        if ($diff) {
            return PhoFile::newTemporaryObject(false, sha1(Strings::force($files, '-')) . '.patch', false)
                          ->putContents($diff . PHP_EOL);
        }

        Log::warning(ts('Files ":files" have no diff', [':files' => $files]));

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
        return $this->o_process->clearArguments()
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
        return $this->o_process->clearArguments()
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
     * @param PhoFileInterface|null $patch_file
     *
     * @return static
     */
    public function apply(?PhoFileInterface $patch_file): static
    {
        if (!$patch_file) {
            Log::warning(ts('Ignoring empty patch filename'));

        } else {
            $output = $this->o_process->clearArguments()
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
     * @param bool        $set_upstream If true, will add the -u modifier to the git push command, automatically setting the target as the upstream branch
     *
     * @return static
     */
    public function push(string $repository, ?string $branch = null, bool $set_upstream = false): static
    {
        $output = $this->o_process->clearArguments()
                                  ->addArgument('push')
                                  ->addArgument($set_upstream ? '-u' : null)
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
        $output = $this->o_process->clearArguments()
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
        $output = $this->o_process->clearArguments()
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
        $output = $this->o_process->clearArguments()
                                  ->addArguments(['fetch', '--all'])
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
        $output = $this->o_process->clearArguments()
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
        $output = $this->o_process->clearArguments()
                                  ->addArgument('rebase')
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }
}
