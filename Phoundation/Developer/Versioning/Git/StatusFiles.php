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
     * @var GitInterface $_git
     */
    protected GitInterface $_git;


    /**
     * StatusFiles class constructor
     *
     * @param RepositoryInterface|PhoPathInterface|null $_parent
     */
    public function __construct(RepositoryInterface|PhoPathInterface|null $_parent = null)
    {
        if ($_parent instanceof RepositoryInterface) {
            $this->_repository = $_parent;
            $this->_parent     = $_parent->getPathObject();

        } else {
            $this->_parent = $_parent;
        }

        $this->setRestrictionsObject($_parent?->getRestrictionsObject() ?? new PhoRestrictions())
             ->setAcceptedDataTypes([PhoPathInterface::class])
             ->___construct($this->_parent);
    }


    /**
     * Returns a new StatusFiles object
     *
     * @param RepositoryInterface|PhoPathInterface|null $_parent
     *
     * @return static
     */
    public static function new(RepositoryInterface|PhoPathInterface|null $_parent = null): static
    {
        return new static($_parent);
    }


    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->source = [];

        $files = $this->_git_process->clearArguments()
                                     ->addArgument('status')
                                     ->addArgument($this->_path)
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
                                                     ->setRepositoryObject($this->_repository);

                } else {
                    $this->source[$file] = StatusFile::new($status, new PhoFile($file, $this->getRestrictionsObject()))
                                                     ->setRepositoryObject($this->_repository);
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
     * @param array             $filters        [[]]
     * @param string|null       $id_column      'file'
     * @param bool              $human_readable [false] If true, will return the status in human readable words instead of the two letter code
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'file', bool $human_readable = false): static
    {
        $list = [];

        if (empty($columns)) {
            $columns = [
                'file'   => tr('File'),
                'status' => tr('Status'),
            ];
        }

        foreach ($this as $file => $_status) {
            $entry = [];

            foreach ($columns as $column => $label) {


                $entry[$column] = match ($column) {
                    'branch'          => $_status->getRepositoryObject()?->getCurrentBranch(),
                    'file'            => $file,
                    'status'          => match ($_status->getStatus()) {
                                             'AD', 'DU', 'UD', 'D ', ' D', 'UU', 'RD', 'both modified' => CliColor::apply($_status->getStatus(), 'red'),
                                             'AM', 'A ', 'MM', 'M ', 'RM', 'T '                        => CliColor::apply($_status->getStatus(), 'blue'),
                                             ' M', 'R '                                                => CliColor::apply($_status->getStatus(), 'green'),
                                             '??', '  '                                                => CliColor::apply($_status->getStatus(), 'white'),
                                             default                                                   => CliColor::apply($_status->getStatus(), 'light_gray'),
                                         },
                    'readable_status' => match ($_status->getStatus()) {
                                             'AD', 'DU', 'UD', 'D ', ' D', 'UU', 'RD', 'both modified' => CliColor::apply($_status->getReadableStatus(), 'red'),
                                             'AM', 'A ', 'MM', 'M ', 'RM', 'T '                        => CliColor::apply($_status->getReadableStatus(), 'blue'),
                                             ' M', 'R '                                                => CliColor::apply($_status->getReadableStatus(), 'green'),
                                             '??', '  '                                                => CliColor::apply($_status->getReadableStatus(), 'white'),
                                             default                                                   => CliColor::apply($_status->getReadableStatus(), 'light_gray'),
                                         },
                    default           => throw new OutOfBoundsException(ts('Unknown column ":column" specified', [
                        ':column' => $column,
                    ])),
                };
            }

            $list[$file] = $entry;
        }

        Cli::displayTable($list, $columns, $id_column);
        return $this;
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param PhoDirectoryInterface $_target_path
     *
     * @return static
     */
    public function patch(PhoDirectoryInterface $_target_path): static
    {
        try {
            // Add all paths to index, create the patch file, apply it, delete it, done
            $this->getGit()->add();

            $_patch_file = $this->getPatchFile(true);

            $this->getGit()->reset('HEAD');

            if ($_patch_file) {
                Git::new($_target_path)->apply($_patch_file);
                PhoFile::new($_patch_file, PhoRestrictions::newTemporary(true, 'StatusFiles::patch()'))->delete();
            }

            return $this;

        } catch (ProcessFailedException $e) {
            Log::warning(ts('Patch failed to apply for target path ":path" with following exception', [
                ':path' => $_target_path,
            ]));
            Log::warning($e->getMessages());
            Log::warning($e->getDataKey('output'));

            if (isset($_patch_file)) {
                // Delete the temporary patch file
                Core::ExecuteIfNotInTestMode(function () use ($_patch_file) {
                    PhoFile::new($_patch_file, PhoRestrictions::new(DIRECTORY_TMP, true))->delete();
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
                    ':patch' => isset_get($_patch_file),
                    ':path'  => $_target_path,
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
        if (!isset($this->_git)) {
            $this->_git = Git::new($this->_path->getDirectoryObject());
        }

        return $this->_git;
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
        return Git::new($this->_path->getParentDirectoryObject())->saveDiff($this->_path->getBasename(), $cached);
    }
}
