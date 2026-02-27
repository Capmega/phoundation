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
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Phoundation\Exception\NotARepositoryException;
use Phoundation\Developer\Versioning\Git\Branches\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Enums\EnumGitSelected;
use Phoundation\Developer\Versioning\Git\Exception\GitBranchIsBehindRemoteBranchException;
use Phoundation\Developer\Versioning\Git\Exception\GitBranchNotExistException;
use Phoundation\Developer\Versioning\Git\Exception\GitException;
use Phoundation\Developer\Versioning\Git\Exception\GitHasNoRemoteBranchException;
use Phoundation\Developer\Versioning\Git\Exception\GitNoBranchSelectedException;
use Phoundation\Developer\Versioning\Git\Exception\GitTagNotExistException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Versioning;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\DirectoryNotExistsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
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
     * @var PhoDirectoryInterface $_directory
     */
    protected PhoDirectoryInterface $_directory;

    /**
     * The git process
     *
     * @var ProcessInterface $_process
     */
    protected ProcessInterface $_process;


    /**
     * Git class constructor
     *
     * @param PhoDirectoryInterface $_directory
     */
    public function __construct(PhoDirectoryInterface $_directory)
    {
        $this->setDirectoryObject($_directory);
    }


    /**
     * Generates and returns a new Git object
     *
     * @param PhoDirectoryInterface $_directory
     *
     * @return static
     */
    public static function new(PhoDirectoryInterface $_directory): static
    {
        return new static($_directory);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(): PhoDirectoryInterface
    {
        return $this->_directory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $_directory
     *
     * @return static
     * @throws DirectoryNotExistsException
     * @throws NotARepositoryException
     */
    public function setDirectoryObject(PhoDirectoryInterface $_directory): static
    {
        $this->_directory = $_directory->makeAbsolute()->checkReadable();
        $this->_process   = Process::new('git')
                                    ->setExecutionDirectory($this->_directory)
                                    ->setTimeout(300)
                                    ->setProcessFailedHandler(function ($e) {
                                        if (!$this->isRepository()) {
                                            if (!$this->_directory->exists()) {
                                                throw DirectoryNotExistsException::new(ts('The path ":path" is not a git repository', [
                                                    ':path' => $this->_directory->getSource()
                                                ]), $e)->addData([
                                                    'path' => $this->_directory
                                                ]);
                                            }

                                            throw NotARepositoryException::new(ts('The path ":path" is not a git repository', [
                                                ':path' => $this->_directory->getSource()
                                            ]), $e)->addData([
                                                'path' => $this->_directory
                                            ]);
                                        }
                                    });

        return $this;
    }


    /**
     * Returns true if the path for this Git object is an actual GIT repository
     *
     * @return bool
     */
    public function isRepository(): bool
    {
        return $this->_directory->addDirectory('.git')->exists();
    }


    /**
     * Throws a NotARepositoryException exception if the current directory for this GIT object is not a git repository
     *
     * @return static
     * @throws NotARepositoryException
     */
    public function checkIsRepository(): static
    {
        if ($this->isRepository()) {
            return $this;
        }

        throw new NotARepositoryException(ts('The path ":path" is not a git repository', [
            'path' => $this->_directory->getSource()
        ]));
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
        $output = $this->_process->clearArguments()
                                  ->addArgument('clone')
                                  ->addArgument($url)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Returns the current git branch for this directory
     *
     * @param string $branch               The branch name to check
     * @param bool   $from_remotes [false] If true, will also check remotes for the requested branch
     *
     * @return bool
     */
    public function branchExists(string $branch, bool $from_remotes = false): bool
    {
        $this->verifyBranch($branch);
        return array_key_exists($branch, $this->getBranches($from_remotes));
    }


    /**
     * Returns true if the current git repository has the specified branch selected
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranchSelected(string $branch): bool
    {
        return $this->getSelectedBranch() === $branch;
    }


    /**
     * Returns true if the current git repository has the specified tag selected
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTagSelected(string $tag): bool
    {
        return $this->getSelectedTag() === $tag;
    }


    /**
     * Returns true if this repository has a branch selected
     *
     * @return bool
     */
    public function hasTypeBranchSelected(): bool
    {
        return (bool) $this->getSelectedBranch();
    }


    /**
     * Returns true if this repository has a tag selected
     *
     * @return bool
     */
    public function hasTypeTagSelected(): bool
    {
        return (bool) $this->getSelectedTag();
    }


    /**
     * Returns the git selected type (branch, tag, detached)
     *
     * @return EnumGitSelected
     */
    public function getSelectedType(): EnumGitSelected
    {
        if ($this->hasTypeBranchSelected()) {
            return EnumGitSelected::branch;
        }

        if ($this->hasTypeTagSelected()) {
            return EnumGitSelected::tag;
        }

        return EnumGitSelected::detached;
    }


    /**
     * Returns true if the selected type for this repository matches the specified type
     *
     * @param EnumGitSelected $selected
     *
     * @return bool
     */
    public function hasSelectedType(EnumGitSelected $selected): bool
    {
        return $this->getSelectedType() === $selected;
    }


    /**
     * Returns the current git branch for this directory
     *
     * @param bool $return_if_detached [false] If true will return the current branch if HEAD is detached
     *
     * @return string|null
     */
    public function getSelectedBranch(bool $return_if_detached = false): ?string
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('branch')
                                  ->executeReturnArray();

        foreach ($output as $line) {
            if (str_starts_with(trim($line), '*')) {
                $return = trim(Strings::from($line, '*'));

                if (preg_match_all('/^\(HEAD detached at (.+?)\)$/', $return, $matches)) {
                    if (!$return_if_detached) {
                        return null;
                    }

                    $return = $matches[1][0];
                }

                return $return;
            }
        }

        throw new GitException(tr('No branch selected for directory ":directory"', [
            ':directory' => $this->_directory,
        ]));
    }


    /**
     * Returns the current git branch for this directory
     *
     * @return string|null
     */
    public function getSelectedTag(): ?string
    {
        if ($this->getSelectedBranch()) {
            // A branch was selected, so no tag can be selected
            return null;
        }

        $tags = $this->getTags();
        $tag  = $this->getSelectedBranch(true);

        if (array_key_exists($tag, $tags)) {
            // This is an existing tag, it is the correct tag
            return $tag;
        }

        // No branch or tag is selected, so likely were in detached mode
        return null;
    }


    /**
     * Returns whether git signing has been enabled in configuration or not
     *
     * @return bool
     */
    public static function getConfigSigned(): bool
    {
        return config()->getBoolean('versioning.git.signed', true);
    }


    /**
     * Returns either the specified $sign when the value is true or false. When null, will return the default from Git::getConfigSign()
     *
     * @param bool|null $signed
     *
     * @return bool
     */
    public static function selectSigned(?bool $signed): bool
    {
        return $signed ?? Git::getConfigSigned();
    }


    /**
     * Sets the current git branch for this directory
     *
     * @param string      $branch              The name of the branch to select
     * @param bool        $auto_create         [false] If true, will automatically create the branch if it does not yet exist
     * @param string|bool $upstream            [false] If specified, will automatically push the branch upstream to either the
     *                                         default remote (if this variable is true), or the specified remote (if
     *                                         this variable is a string containing the remote where to set upstream to)
     *
     * @return static
     */
    public function selectBranch(string $branch, bool $auto_create = false, string|bool $upstream = false): static
    {
        $this->verifyBranch($branch);

        if (!$this->branchExists($branch)) {
            // The requested branch does not exist!
            if (!$auto_create) {
                throw GitBranchNotExistException::new(ts('Cannot set current branch to ":branch" on repository ":repository", the branch does not exist', [
                    ':branch'     => $branch,
                    ':repository' => $this->_directory
                ]))->addHint(ts('Set $auto_create to true to automatically create the requested branch from the currently selected branch if it does not exist'));
            }

            // Auto create this branch first before selecting it
            $this->createBranch($branch, upstream: $upstream);
        }

        return $this->checkout($branch);
    }


    /**
     * Sets the current git tag for this directory
     *
     * @param string $tag The name of the tag to select
     *
     * @return static
     */
    public function selectTag(string $tag): static
    {
        $this->verifyTag($tag);

        if (!$this->tagExists($tag)) {
            // The requested tag does not exist!
            throw GitTagNotExistException::new(ts('Cannot set current tag to ":tag" on repository ":repository", the tag does not exist', [
                ':tag'        => $tag,
                ':repository' => $this->_directory
            ]))->addHint(ts('Set $auto_create to true to automatically create the requested tag from the currently selected tag if it does not exist'));
        }

        return $this->checkout($tag);
    }


    /**
     * Creates the specified GIT branch for this directory
     *
     * Note: This will NOT select the branch, only create it
     *
     * @param string      $branch   The new branch name to create
     * @param bool        $reset    [false] If true, will reset the tree before creating the new branch
     * @param string|bool $upstream [false] If true, or repository name, will set this remote as the default upstream
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false, string|bool $upstream = false): static
    {
        $this->verifyBranch($branch);

        if ($this->branchExists($branch)) {
            throw new GitException(ts('Cannot create new branch ":branch" on repository ":repository", the branch already exists', [
                ':branch'     => $branch,
                ':repository' => $this->_directory
            ]));
        }

        $current = $this->getSelectedBranch();
        $output  = $this->_process->clearArguments()
                                   ->addArguments(['checkout', ($reset ? '-B' : '-b')])
                                   ->addArgument($branch)
                                   ->executeReturnArray();

        Log::notice($output, 1, false);

        if ($upstream) {
            return $this->push($this->getDefaultRemote($upstream), $branch, true);
        }

        return $this->selectBranch($current);
    }


    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch         The name of the branch to delete
     * @param bool   $force          [false] If true, will force deletion, even if there is a reason to stop the deletion, like
     *                               the branch containing changes that have not been merged anywhere yet
     *
     * @return static
     */
    public function deleteBranch(string $branch, bool $force = false): static
    {
        $this->verifyBranch($branch);

        $output = $this->_process->clearArguments()
                                  ->addArguments(['branch', '-d', ($force or FORCE ? '-f' : null)])
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch The branch to remove from the remote repository
     * @param string $remote The remote repository from which to remove the branch
     *
     * @return static
     */
    public function deleteBranchRemote(string $branch, string $remote): static
    {
        $this->checkRemoteExists($remote)
             ->verifyBranch($branch);

        if ($this->branchExists($branch, true)) {
            $output = $this->_process->clearArguments()
                                      ->addArguments(['push', $remote, ':' . $branch])
                                      ->executeReturnArray();

            Log::notice($output, 1, false);

        } else {
            Log::warning(ts('Not deleting branch ":branch" from remote ":remote" from repository ":repository", the branch does not exist on the remote', [
                ':branch'     => $branch,
                ':remote'     => $remote,
                ':repository' => $this->_directory,
            ]), 3);
        }

        return $this;
    }


    /**
     * Returns a list of available git branches
     *
     * @param bool        $all      [false] If true, will return all branches, including the ones that have not been checked out locally
     * @param string|null $contains [null]  If specified, will filter branches that contain the specified revision id
     *
     * @return array
     */
    public function getBranches(bool $all = false, ?string $contains = null): array
    {
        if ($all and $contains) {
            throw new OutOfBoundsException(ts('Cannot use both filters $all and $contains at the same time'));
        }

        $source  = [];
        $results = $this->_process->clearArguments()
                                  ->addArgument('branch')
                                  ->addArgument('--quiet')
                                  ->addArgument((ALL or $all) ? '-a' : null)
                                  ->addArguments(($contains)   ? ['--contains', $contains] : null)
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
     * Deletes the specified GIT tag for this directory
     *
     * @param string $tag
     * @param bool   $force
     *
     * @return static
     */
    public function deleteTag(string $tag, bool $force = false): static
    {
        $this->verifyTag($tag);

        $output = $this->_process->clearArguments()
                                  ->addArguments(['tag', '-d', ($force or FORCE ? '-f' : null)])
                                  ->addArgument($tag)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Returns the current git tag for this directory
     *
     * @param string $tag
     *
     * @return bool
     */
    public function tagExists(string $tag): bool
    {
        $this->verifyTag($tag);
        return array_key_exists($tag, $this->getTags());
    }


    /**
     * Deletes the specified GIT tag for this directory
     *
     * @param string $tag    The tag to remove from the remote repository
     * @param string $remote The remote repository from which to remove the tag
     *
     * @return static
     */
    public function deleteTagRemote(string $tag, string $remote): static
    {
        $this->checkRemoteExists($remote)->verifyTag($tag);

        if ($this->tagExists($tag)) {
            $output = $this->_process->clearArguments()
                                      ->addArguments(['push', $remote, ':' . $tag])
                                      ->executeReturnArray();

            Log::notice($output, 1, false);

        } else {
            Log::warning(ts('Not deleting tag ":tag" from remote ":remote" from repository ":repository", the tag does not exist on the remote', [
                ':tag'        => $tag,
                ':remote'     => $remote,
                ':repository' => $this->_directory,
            ]), 3);
        }

        return $this;
    }


    /**
     * Returns a list of available git tags
     *
     * @return array
     */
    public function getTags(): array
    {
        $return = $this->_process->clearArguments()
                                  ->addArgument('tag')
                                  ->addArgument('-l')
                                  ->executeReturnArray();

        Log::notice($return, 1, false);
        return Arrays::valueToKeys($return);
    }


    /**
     * Creates the specified tag for this git repository
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message         [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool|null   $signed          [FALSE] If true, will sign the tag (Requires git has been configured for signing messages)
     *
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static
    {
        if ($this->branchExists($tag)) {
            throw new GitException(ts('Cannot create new tag ":tag" on repository ":repository", the tag already exists', [
                ':tag'        => $tag,
                ':repository' => $this->_directory
            ]));
        }

        $return = $this->_process->clearArguments()
                                  ->addArgument('tag')
                                  ->addArguments(['-a', $tag])
                                  ->addArguments($message ? ['-m', $message] : null)
                                  ->addArguments($this->selectSigned($signed) ? ['-s'] : null)
                                  ->executeReturnArray();

        Log::notice($return, 1, false);
        return $this;
    }


    /**
     * Creates the specified lightweight tag for this git repository
     *
     * @param string $tag The name for the tag
     *
     * @return static
     */
    public function createLightweightTag(string $tag): static
    {
        if ($this->branchExists($tag)) {
            throw new GitException(ts('Cannot create new tag ":tag" on repository ":repository", the tag already exists', [
                ':tag'        => $tag,
                ':repository' => $this->_directory
            ]));
        }

        $return = $this->_process->clearArguments()
                                  ->addArgument('tag')
                                  ->addArguments($tag)
                                  ->executeReturnArray();

        Log::notice($return, 1, false);
        return $this;
    }


    /**
     * Stashes the changes in the current repository
     *
     * @param PhoPathInterface|array|string|null $_paths
     *
     * @return static
     */
    public function stash(PhoPathInterface|array|string|null $_paths = null): static
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('stash')
                                  ->addArgument('--')
                                  ->addArguments($_paths)
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
        $output = $this->_process->clearArguments()
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
        return $this->_process->clearArguments()
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
        $results = $this->_process->clearArguments()
                                   ->addArgument('stash')
                                   ->addArgument('list')
                                   ->executeReturnArray();

        foreach ($results as $result) {
            preg_match_all('/stash@\{(\d+)}:\s(.+)/', $result, $matches);
            $return[$matches[0][0]] = $matches[2][0];
        }

        return $return;
    }


    /**
     * Checks out the specified branches or directories for this git directory
     *
     * @param Stringable|array|string $branches_or_directories
     *
     * @return static
     */
    public function checkout(Stringable|array|string $branches_or_directories): static
    {
        $output = $this->_process->clearArguments()
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
        $return = $this->_process->clearArguments()
                                  ->addArgument('remote')
                                  ->executeReturnArray();

        Log::notice($return, 1, false);
        return Arrays::valueToKeys($return);
    }


    /**
     * Returns the default repository if the specified repository is empty
     *
     * @param string|bool|null $repository The repository to test
     *
     * @return string
     */
    public function getDefaultRemote(string|bool|null $repository): string
    {
        if (is_string($repository) and $repository) {
            return $repository;
        }

        return config()->getString('developer.versioning.repositories.remote');
    }


    /**
     * Returns true if the specified remote exists for this repository
     *
     * @param string $remote
     *
     * @return bool
     */
    public function remoteExists(string $remote): bool
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
    public function checkRemoteExists(string $remote): static
    {
        if ($this->remoteExists($remote)) {
            return $this;
        }

        throw new GitException(ts('The specified remote ":remote" does not exist for the GIT repository ":repository"', [
            ':remote'     => $remote,
            ':repository' => $this->_directory
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
        $output = $this->_process->clearArguments()
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
        $output = $this->_process->clearArguments()
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
        $output = $this->_process->clearArguments()
                                  ->addArgument('add')
                                  ->addArgument($files)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Moves or renames the specified source file to the target
     *
     * @param PhoFileInterface $source
     * @param PhoFileInterface $target
     *
     * @return static
     */
    public function mv(PhoFileInterface $source, PhoFileInterface $target): static
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('mv')
                                  ->addArguments([$source, $target])
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Moves or renames the specified source file to the target
     *
     * @param PhoFileInterface $source
     * @param PhoFileInterface $target
     *
     * @return static
     */
    public function move(PhoFileInterface $source, PhoFileInterface $target): static
    {
        return $this->mv($source, $target);
    }


    /**
     * Commits the current indexed files to the git database
     *
     * @param string    $message
     * @param bool|null $signed
     *
     * @return static
     */
    public function commit(string $message, ?bool $signed = null): static
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('commit')
                                  ->addArgument('-m')
                                  ->addArgument($message)
                                  ->addArgument($this->selectSigned($signed) ? '-s' : null)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Cancels the last commit made to the git database and places the changes back as changes in the working tree
     *
     * @return static
     */
    public function uncommit(): static
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('reset')
                                  ->addArgument('HEAD^')
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
        return (bool) $this->getStatusFilesObject($directory ?? $this->_directory)
                           ->getCount();
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
        return StatusFiles::new($path ?? $this->_directory)->scanChanges();
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
        return $this->_process->clearArguments()
                               ->addArgument('diff')
                               ->addArgument(NOCOLOR ? '--no-color' : null)
                               ->addArgument($cached ? '--cached' : null)
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
        return $this->_process->clearArguments()
                               ->addArgument('log')
                               ->addArgument(NOCOLOR ? '--no-color' : null)
                               ->addArgument($cached ? '--cached' : null)
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
            $output = $this->_process->clearArguments()
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
     * @param string|null $repository   [null]  The remote repository to push to. If null, will push to the default
     *                                          repository
     * @param string|null $branch       [null]  If specified will push only this branch
     * @param bool        $push_tags    [true]  If true, will push the tags as well
     * @param bool        $set_upstream [false] If true, will add the -u modifier to the git push command, automatically
     *                                          setting the target as the upstream
     *                                  branch
     *
     * @return static
     */
    public function push(?string $repository = null, ?string $branch = null, bool $push_tags = true, bool $set_upstream = false): static
    {
        $this->verifyBranch($branch);

        try {
            $output = $this->_process->clearArguments()
                                      ->addArgument('push')
                                      ->addArgument($set_upstream ? '-u' : null)
                                      ->addArguments([
                                          $this->getDefaultRemote($repository),
                                          $branch,
                                      ])
                                      ->executeReturnArray();

            Log::notice($output, 1, false);

        } catch (ProcessFailedException $e) {
            if (Arrays::containsNeedles($e->getDataKey('output'), ['failed to push some refs to'])) {
                if (Arrays::containsNeedles($e->getDataKey('output'), ['Updates were rejected because a pushed branch tip is behind its remote'])) {
                    // Is the current branch that we are trying to push amongst the branches that failed to push? If not, we are all fine!
                    $branches = Arrays::getContainsNeedles($e->getDataKey('output'), ['! [rejected]']);

                    if ($branches) {
                        foreach ($branches as $check_branch) {
                            // Clean the branch, check if it is the one we are interested in
                            $check_branch = Strings::from($check_branch, '! [rejected]');
                            $check_branch = trim($check_branch);
                            $check_branch = Strings::until($check_branch, '->');
                            $check_branch = trim($check_branch);

                            if ($check_branch === $branch) {
                                throw GitBranchIsBehindRemoteBranchException::new(ts('Cannot pull branch ":branch" on repository ":repository", the branch is behind its remote branch', [
                                    ':branch'     => $this->getSelectedBranch(),
                                    ':repository' => $this->_directory,
                                ]))
                                ->addHint(ts('This could potentially be fixed by going to the repository directory ":repository" and executing "git pull" on branch ":branch"', [
                                    ':branch'     => $this->getSelectedBranch(),
                                    ':repository' => $this->_directory,
                                ]));
                            }
                        }
                    }

                    // The branch causing the issue is NOT the branch we are interested in, we should be able to safely ignore this exception
                    Log::notice($e->getDataKey('output'), 1, false);
                    return $this;
                }
            }

            if (Arrays::containsNeedles($e->getDataKey('output'), ['You are not currently on a branch'])) {
                throw GitNoBranchSelectedException::new(ts('Cannot execute a general push on repository ":repository", it has no branch selected', [
                    ':repository' => $this->_directory,
                ]))
                ->setData([
                    ':repository' => $this->_directory,
                    ':branch'     => $this->getSelectedBranch(),
                    ':type'       => $this->getSelectedType(),
                ])
                ->addHint(ts('The repository ":repository" currently has a ":type" selected. To continue, first select a branch instead', [
                    ':repository' => $this->_directory,
                    ':type'       => $this->getSelectedType(),
                ]));
            }

            if (Arrays::containsNeedles($e->getDataKey('output'), ['You asked to pull from the remote', 'a branch. Because this is not the default configured remote', 'your current branch, you must specify a branch on the command'])) {
                if (empty($branch)) {
                    throw GitHasNoRemoteBranchException::new(ts('Cannot pull branch ":branch" on repository ":repository" without specifying a remote branch, this repository branch has no upstream configured yet', [
                        ':branch'     => $this->getSelectedBranch(),
                        ':repository' => $this->_directory,
                    ]))
                    ->addHint(ts('This could potentially be fixed by going to the repository directory ":repository" and executing "git branch --set-upstream-to=origin/:branch"', [
                        ':branch'     => $this->getSelectedBranch(),
                        ':repository' => $this->_directory,
                    ]));
                }
            }

            throw $e;
        }

        return $this;
    }


    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @param string|null $repository
     * @param string|null $branch
     *
     * @return static
     */
    public function pull(?string $repository, ?string $branch): static
    {
        $this->verifyBranch($branch);

        try {
            $output = $this->_process->clearArguments()
                                      ->addArgument('pull')
                                      ->addArgument($this->getDefaultRemote($repository))
                                      ->addArgument($branch)
                                      ->executeReturnArray();

            Log::notice($output, 1, false);

        } catch (ProcessFailedException $e) {
            if (Arrays::containsNeedles($e->getDataKey('output'), ['You are not currently on a branch'])) {
                throw GitNoBranchSelectedException::new(ts('Cannot pull on repository ":repository", it has no branch selected', [
                    ':repository' => $this->_directory,
                ]))
                ->setData([
                    ':repository' => $this->_directory,
                    ':branch'     => $this->getSelectedBranch(),
                    ':type'       => $this->getSelectedType(),
                ])
                ->addHint(ts('The repository ":repository" currently has a ":type" selected. To continue, first select a branch instead', [
                    ':repository' => $this->_directory,
                    ':type'       => $this->getSelectedType(),
                ]));
            }

            if (Arrays::containsNeedles($e->getDataKey('output'), ['You asked to pull from the remote', 'a branch. Because this is not the default configured remote', 'your current branch, you must specify a branch on the command'])) {
                if (empty($branch)) {
                    throw GitHasNoRemoteBranchException::new(ts('Cannot pull branch ":branch" on repository ":repository" without specifying a remote branch, this repository branch has no upstream configured yet', [
                        ':branch'     => $this->getSelectedBranch(),
                        ':repository' => $this->_directory,
                    ]))
                    ->addHint(ts('This could potentially be fixed by going to the repository directory ":repository" and executing "git branch --set-upstream-to=origin/:branch"', [
                        ':branch'     => $this->getSelectedBranch(),
                        ':repository' => $this->_directory,
                    ]));
                }
            }

            throw $e;
        }

        return $this;
    }


    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @param string|null $repository        The repository to pull from. If not specified, the "origin" default will be
     *                                       used, unless an upstream was specified for the current branch
     * @param bool        $all        [true] Will execute git fetch --all, fetch all remotes, except for the ones that
     *                                       has the remote.
     *
     * @return static
     */
    public function fetch(?string $repository, bool $all = true): static
    {
        $output = $this->_process->clearArguments()
                                  ->addArgument('fetch')
                                  ->addArgument($all ? '--all' : null)
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
        $output = $this->_process->clearArguments()
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
        $this->verifyBranch($branch);

        $output = $this->_process->clearArguments()
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
        $this->verifyBranch($branch);

        $output = $this->_process->clearArguments()
                                  ->addArgument('rebase')
                                  ->addArgument($branch)
                                  ->executeReturnArray();

        Log::notice($output, 1, false);
        return $this;
    }


    /**
     * Throws an OutOfBoundsException if the specified branch name is invalid
     *
     * @param string|null $branch
     *
     * @return static
     */
    protected function verifyBranch(?string $branch): static
    {
        if ($branch) {
            if (str_starts_with($branch, ':')) {
                throw new OutOfBoundsException(ts('Invalid git branch name ":branch" specified', [
                    ':branch' => $branch
                ]));
            }
        }

        return $this;
    }


    /**
     * Throws an OutOfBoundsException if the specified tag name is invalid
     *
     * @param string $tag
     *
     * @return static
     * @throws OutOfBoundsException
     */
    protected function verifyTag(string $tag): static
    {
        if (str_starts_with($tag, ':')) {
            throw new OutOfBoundsException(ts('Invalid git tag name ":tag" specified', [
                ':tag' => $tag
            ]));
        }

        return $this;
    }


    /**
     * Returns the commit objects in reverse chronological order
     *
     * @return array
     */
    public function listRevisions(): array
    {
        return $this->_process->clearArguments()
                               ->addArguments(['rev-list', ALL ? '--all' : null])
                               ->executeReturnArray();
    }


    /**
     * Searches the entire git history for the specified keyword
     *
     * @param string $keyword        The keyword to search for
     * @param bool   $grouped [true] If true, will return the results grouped by revision and file. If false, will return the results directly from GIT
     *
     * @return IteratorInterface
     */
    public function grep(string $keyword, bool $grouped = true): IteratorInterface
    {
        $return  = [];
        $results = $this->_process->clearArguments()
                                   ->addArguments(['grep', '-n', $keyword])
                                   ->addArgument('$(git rev-list --all)', false, false)
                                   ->executeReturnArray();

        if (!$grouped) {
            return new Iterator($results);
        }

        // Group the results by revision and file
        foreach ($results as $result) {
            $revision = Strings::until($result, ':');
            $file     = Strings::cut($result, ':', ':', 1);
            $line     = Strings::cut($result, ':', ':', 2);
            $content  = Strings::from($result, ':', 3);

            if (!array_key_exists($revision, $return)) {
                $return[$revision] = [];
            }

            if (!array_key_exists($file, $return[$revision])) {
                $return[$revision][$file] = [];
            }

            $return[$revision][$file][$line] = $content;
        }

        return new Iterator($return);
    }


    /**
     * Returns the repositories where the specified revision is a member of
     *
     * @param string $revision
     *
     * @return array
     */
    public function getBranchesContainingRevision(string $revision): array
    {
        $source  = [];
        $results = $this->_process->clearArguments()
                                   ->addArguments(['branch', '--quiet', '--no-color'])
                                   ->addArguments(['--contains', $revision])
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
}
