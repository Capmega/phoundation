<?php

/**
 * Class StatusFiles
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Exception\GitStatusException;
use Phoundation\Developer\Versioning\Git\Exception\GitUnknownStatusException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoFilesCore;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


class StatusFiles extends PhoFilesCore implements StatusFilesInterface
{
    use TraitDataObjectRepository;
    use TraitGitProcess {
        __construct as protected ___construct;
    }


    /**
     * A git object specifically for this path
     *
     * @var GitInterface $o_git
     */
    protected GitInterface $o_git;


    /**
     * StatusFiles class constructor
     *
     * @param RepositoryInterface|PhoPathInterface|null $o_parent
     */
    public function __construct(RepositoryInterface|PhoPathInterface|null $o_parent = null)
    {
        if ($o_parent instanceof RepositoryInterface) {
            $this->o_repository = $o_parent;
            $this->o_parent     = $o_parent->getPathObject();

        } else {
            $this->o_parent = $o_parent;
        }

        $this->setRestrictionsObject($o_parent?->getRestrictionsObject() ?? new PhoRestrictions())
             ->setAcceptedDataTypes([PhoPathInterface::class])
             ->___construct($this->o_parent);
    }


    /**
     * Returns a new StatusFiles object
     *
     * @param RepositoryInterface|PhoPathInterface|null $o_parent
     *
     * @return static
     */
    public static function new(RepositoryInterface|PhoPathInterface|null $o_parent = null): static
    {
        return new static($o_parent);
    }


    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->source = [];

        $files = $this->o_git_process->clearArguments()
                                     ->addArgument('status')
                                     ->addArgument($this->o_path)
                                     ->addArgument('--porcelain')
                                     ->executeReturnArray();

        // Parse output
        foreach ($files as $file) {
            if (str_starts_with(strtolower($file), 'warning:')) {
                throw GitStatusException::new(tr('Failed to fetch git status for file ":file", it failed with ":error"', [
                    ':file'  => Strings::cut($file, "'", "'"),
                    ':error' => Strings::fromReverse($file, ': '),
                ]))->setData([
                    ':file'  => Strings::cut($file, "'", "'"),
                    ':error' => Strings::fromReverse($file, ': '),
                ]);
            }

            $status = substr($file, 0, 2);
            $file   = substr($file, 3);
            $target = null;

            try {
                if (str_contains($file, ' -> ')) {
                    $target = Strings::from($file, ' -> ');
                    $file   = Strings::until($file, ' -> ');

                    $this->source[$file] = StatusFile::new($status, new PhoFile($file, $this->getRestrictionsObject()), new PhoFile($target, $this->getRestrictionsObject()))
                                                     ->setRepositoryObject($this->o_repository);

                } else {
                    $this->source[$file] = StatusFile::new($status, new PhoFile($file, $this->getRestrictionsObject()))
                                                     ->setRepositoryObject($this->o_repository);
                }

            } catch (GitUnknownStatusException $e) {
                throw GitUnknownStatusException::new(tr('Unknown git status ":status" encountered for file ":file"', [
                    ':file'   => $file,
                    ':status' => $status,
                ]), $e)->setData([
                    'file'   => $file,
                    'status' => $status,
                ]);
            }
        }

        return $this;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'file'): static
    {
        $list = [];

        if (empty($columns)) {
            $columns = [
                'file'   => tr('File'),
                'status' => tr('Status'),
            ];
        }

        foreach ($this as $file => $o_status) {
            $entry = [];

            foreach ($columns as $column => $label) {
                switch ($column) {
                    case 'repository':
                        $entry[$column] = $o_status->getRepositoryObject()?->getDisplayName();
                        break;

                    case 'branch':
                        $entry[$column] = $o_status->getRepositoryObject()?->getCurrentBranch();
                        break;

                    case 'file':
                        $entry[$column] = $file;
                        break;

                    case 'status':
                        if (trim(substr($o_status->getStatus(), 0, 1))) {
                            $entry[$column] = CliColor::apply($o_status->getStatus(), 'green');

                        } else {
                            $entry[$column] = CliColor::apply($o_status->getStatus(), 'red');
                        }

                        break;

                    case 'readable_status':
                        if (trim(substr($o_status->getStatus(), 0, 1))) {
                            $entry[$column] = CliColor::apply($o_status->getReadableStatus(), 'green');

                        } else {
                            $entry[$column] = CliColor::apply($o_status->getReadableStatus(), 'red');
                        }

                        break;

                    default:
                        throw new OutOfBoundsException(ts('Unknown column ":column" specified', [
                            ':column' => $column,
                        ]));
                }
            }

            $list[$file] = $entry;
        }

        Cli::displayTable($list, $columns, $id_column);

        return $this;
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param PhoDirectoryInterface $o_target_path
     *
     * @return static
     */
    public function patch(PhoDirectoryInterface $o_target_path): static
    {
        try {
            // Add all paths to index, create the patch file, apply it, delete it, done
            $this->getGit()->add();

            $o_patch_file = $this->getPatchFile(true);

            $this->getGit()->reset('HEAD');

            if ($o_patch_file) {
                Git::new($o_target_path)->apply($o_patch_file);
                PhoFile::new($o_patch_file, PhoRestrictions::newTemporary(true, 'StatusFiles::patch()'))->delete();
            }

            return $this;

        } catch (ProcessFailedException $e) {
            Log::warning(ts('Patch failed to apply for target path ":path" with following exception', [
                ':path' => $o_target_path,
            ]));
            Log::warning($e->getMessages());
            Log::warning($e->getDataKey('output'));

            if (isset($o_patch_file)) {
                // Delete the temporary patch file
                Core::ExecuteIfNotInTestMode(function () use ($o_patch_file) {
                    PhoFile::new($o_patch_file, PhoRestrictions::new(DIRECTORY_TMP, true))->delete();
                }, tr('Removing git patch files'));
            }

            foreach ($e->getDataKey('output') as $line) {
                if (str_contains($line, 'patch does not apply')) {
                    $files[] = Strings::cut($line, 'error: ', ': patch does not apply');
                }

                if (str_ends_with($line, ': No such file or directory')) {
                    $files[] = Strings::cut($line, 'error: ', ': No such file or directory');
                }
            }

            if (isset($files)) {
                // Specific files failed to apply
                throw GitPatchFailedException::new(tr('Failed to apply patch ":patch" to path ":path"', [
                    ':patch' => isset_get($o_patch_file),
                    ':path'  => $o_target_path,
                ]), $e)->addData([
                    'files' => $files,
                ]);
            }

            // We have a different git failure
            throw $e;
        }
    }


    /**
     * Returns a git object for this path
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface
    {
        if (!isset($this->o_git)) {
            $this->o_git = Git::new($this->o_path->getDirectoryObject());
        }

        return $this->o_git;
    }


    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @param bool $cached
     *
     * @return PhoFileInterface
     */
    public function getPatchFile(bool $cached = false): PhoFileInterface
    {
        return Git::new($this->o_path->getParentDirectoryObject())->saveDiff($this->o_path->getBasename(), $cached);
    }
}
