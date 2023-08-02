<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Versioning\Git\Exception\GitException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Versioning;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
use Phoundation\Processes\Process;


/**
 * Class Git
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Git extends Versioning implements GitInterface
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
     * @var Process $git
     */
    protected Process $git;


    /**
     * Git class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
    }


    /**
     * Generates and returns a new Git object
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
        $this->path = Filesystem::absolute($path);
        $this->git  = Process::new('git')->setExecutionPath($this->path);

        if (!$this->path) {
            if (!file_exists($path)) {
                throw new OutOfBoundsException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }
        }

        return $this;
    }


    /**
     * Clone the specified URL to this path
     *
     * @return $this
     */
    public function clone(string $url): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('clone')
            ->addArgument($this->url)
            ->addArgument($url)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Returns the current git branch for this path
     *
     * @return string
     */
    public function getBranch(): string
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('branch')
            ->executeReturnArray();

        foreach ($output as $line) {
            if (str_starts_with(trim($line), '*')) {
                return trim(Strings::from($line, '*'));
            }
        }

        throw new GitException(tr('No brach selected for path ":path"', [
            ':path' => $this->path
        ]));
    }


    /**
     * Returns the current git branch for this path
     *
     * @param string $branch
     * @return static
     */
    public function setBranch(string $branch): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('checkout')
            ->addArgument($branch)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Returns all available git repositories
     *
     * @return RemoteRepositories
     */
    public function getRepositories(): RemoteRepositories
    {
        return RemoteRepositories::new()->setPath($this->path);
    }


    /**
     * Returns a list of available git branches
     *
     * @return Branches
     */
    public function getBranches(): Branches
    {
        return Branches::new()->setPath($this->path);
    }


    /**
     * Stashes the git changes
     *
     * @return Stash
     */
    public function getStash(): Stash
    {
        return Stash::new()->setPath($this->path);
    }


    /**
     * Checks out the specified branches or paths for this git path
     *
     * @param array|string $branches_or_paths
     * @return static
     */
    public function checkout(array|string $branches_or_paths): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('checkout')
            ->addArguments($branches_or_paths)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Resets the current branch to the specified revision
     *
     * @param string $revision
     * @param array|string|null $files
     * @return static
     */
    public function reset(string $revision, array|string|null $files = null): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('reset')
            ->addArgument($revision)
            ->addArgument($files)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Apply the specified patch to the specified target file
     *
     * @param array|string|null $files
     * @return static
     */
    public function add(array|string|null $files = null): static
    {
        if (!$files) {
            $files = '.';
        }

        $output = $this->git
            ->clearArguments()
            ->addArgument('add')
            ->addArgument($files)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Resets the current branch to the specified revision
     *
     * @param string $message
     * @param bool $signed
     * @return static
     */
    public function commit(string $message, bool $signed = false): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('commit')
            ->addArgument('-m')
            ->addArgument($message)
            ->addArgument($signed ? '-s' : null)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param string|null $path
     * @return StatusFiles
     */
    public function getStatus(?string $path = null): StatusFiles
    {
        return StatusFiles::new()
            ->setPath($path ?? $this->path)
            ->scanChanges();
    }


    /**
     * Returns if this git path has any changes
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return (bool) $this->getStatus()->getCount();
    }


    /**
     * Get a diff for the specified file
     *
     * @param array|string|null $files
     * @param bool $cached
     * @return string
     */
    public function getDiff(array|string|null $files = null, bool $cached = false): string
    {
        return $this->git
            ->clearArguments()
            ->addArgument('diff')
            ->addArgument('--no-color')
            ->addArgument($cached ? '--cached' : null)
            ->addArgument('--')
            ->addArguments($files)
            ->executeReturnString();
    }


    /**
     * Save the diff for the specified file to the specified target
     *
     * @note Returns NULL if the specified file has no diff
     *
     *
     * @param array|string $files
     * @param bool $cached
     * @return string|null
     */
    public function saveDiff(array|string $files, bool $cached = false): ?string
    {
        $diff = $this->getDiff($files, $cached);

        if ($diff) {
            $file = Path::getTemporary() . sha1(Strings::force($files, '-')) . '.patch';
            file_put_contents($file, $diff . PHP_EOL);
            return $file;
        }

        Log::warning(tr('Files ":files" has / have no diff', [':files' => $files]));
        return null;
    }


    /**
     * Apply the specified patch to the specified target file
     *
     * @param string|null $patch_file
     * @return static
     */
    public function apply(?string $patch_file): static
    {
        if (!$patch_file) {
            Log::warning(tr('Ignoring empty patch filename'));
        } else {
            $output = $this->git
                ->clearArguments()
                ->addArgument('apply')
                ->addArgument('-v')
                ->addArgument('--ignore-whitespace')
                ->addArgument('--ignore-space-change')
                ->addArgument('--whitespace=nowarn')
                ->addArgument($patch_file)
                ->executeReturnArray();

            Log::notice($output, 4, false);
        }

        return $this;
    }


    /**
     * Push the local changes to the remote repository / branch
     *
     * @param string $repository
     * @param string $branch
     * @return static
     */
    public function push(string $repository, string $branch): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('push')
            ->addArgument($repository)
            ->addArgument($branch)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Merge the specified branch into this one
     *
     * @param string $branch
     * @return static
     */
    public function merge(string $branch): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('merge')
            ->addArgument($branch)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }


    /**
     * Rebase the specified branch into this one
     *
     * @param string $branch
     * @return static
     */
    public function rebase(string $branch): static
    {
        $output = $this->git
            ->clearArguments()
            ->addArgument('rebase')
            ->addArgument($branch)
            ->executeReturnArray();

        Log::notice($output, 4, false);
        return $this;
    }
}