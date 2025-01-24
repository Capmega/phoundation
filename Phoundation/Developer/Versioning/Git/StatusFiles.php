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
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
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
    use TraitGitProcess {
        __construct as protected ___construct;
    }


    /**
     * A git object specifically for this path
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * StatusFiles class constructor
     *
     * @param PhoDirectoryInterface $directory
     */
    public function __construct(PhoDirectoryInterface $directory)
    {
        $this->parent              = $directory;
        $this->accepted_data_types = [PhoPathInterface::class];
        $this->restrictions        = $directory->getRestrictions();

        $this->___construct($directory);
    }


    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->source = [];

        $files = $this->git_process->clearArguments()
                                   ->addArgument('status')
                                   ->addArgument($this->directory)
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

            if (str_contains($file, ' -> ')) {
                $target = Strings::from($file, ' -> ');
                $file   = Strings::until($file, ' -> ');
            }

            try {
                $this->source[$file] = StatusFile::new($status, new PhoFile($file), new PhoFile($target));

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

        foreach ($this->getSource() as $file => $status) {
            if (trim(substr($status->getStatus(), 0, 1))) {
                $status = CliColor::apply($status->getStatus()->getReadable(), 'green');

            } else {
                $status = CliColor::apply($status->getStatus()->getReadable(), 'red');
            }

            $list[$file] = ['status' => $status];
        }

        Cli::displayTable($list, ['file'   => tr('File'),
                                  'status' => tr('Status'),
        ], $id_column);

        return $this;
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_path
     *
     * @return static
     */
    public function patch(PhoDirectoryInterface $target_path): static
    {
        try {
            // Add all paths to index, create the patch file, apply it, delete it, done
            $this->getGit()->add();

            $patch_file = $this->getPatchFile(true);

            $this->getGit()->reset('HEAD');

            if ($patch_file) {
                Git::new($target_path)->apply($patch_file);
                PhoFile::new($patch_file, PhoRestrictions::newTemporary(true, 'StatusFiles::patch()'))->delete();
            }

            return $this;

        } catch (ProcessFailedException $e) {
            Log::warning(tr('Patch failed to apply for target directory ":directory" with following exception', [
                ':directory' => $target_path,
            ]));
            Log::warning($e->getMessages());
            Log::warning($e->getDataKey('output'));

            if (isset($patch_file)) {
                // Delete the temporary patch file
                Core::ExecuteIfNotInTestMode(function () use ($patch_file) {
                    PhoFile::new($patch_file, PhoRestrictions::new(DIRECTORY_TMP, true))->delete();
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
                throw GitPatchFailedException::new(tr('Failed to apply patch ":patch" to directory ":directory"', [
                    ':patch'     => isset_get($patch_file),
                    ':directory' => $target_path,
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
        if (!isset($this->git)) {
            $this->git = Git::new($this->directory);
        }

        return $this->git;
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
        return Git::new($this->directory->getParentDirectory())->saveDiff($this->directory->getBasename(), $cached);
    }
}
