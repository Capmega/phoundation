<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Exception\GitStatusException;
use Phoundation\Developer\Versioning\Git\Exception\GitUnknownStatusException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;

/**
 * Class StatusFiles
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class StatusFiles extends Iterator implements StatusFilesInterface
{
    use TraitGitProcess;

    /**
     * A git object specifically for this path
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


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
            $file = trim($file);
            if (str_starts_with(strtolower($file), 'warning:')) {
                throw GitStatusException::new(tr('Failed to fetch git status for file ":file", it failed with ":error"', [
                    ':file'  => Strings::cut($file, "'", "'"),
                    ':error' => Strings::fromReverse($file, ': '),
                ]))
                                        ->setData([
                                            ':file'  => Strings::cut($file, "'", "'"),
                                            ':error' => Strings::fromReverse($file, ': '),
                                        ]);
            }
            $status = substr($file, 0, 2);
            $file   = substr($file, 3);
            $target = '';
            if (str_contains($file, ' -> ')) {
                $target = Strings::from($file, ' -> ');
                $file   = Strings::until($file, ' -> ');
            }
            try {
                $this->source[$file] = StatusFile::new($status, $file, $target);

            } catch (GitUnknownStatusException $e) {
                throw GitUnknownStatusException::new(tr('Unknown git status ":status" encountered for file ":file"', [
                    ':file'   => $file,
                    ':status' => $status,
                ]), $e)
                                               ->setData([
                                                   'file'   => $file,
                                                   'status' => $status,
                                               ]);
            }
        }

        return $this;
    }


    /**
     * Display the files status on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];
        foreach ($this->getSource() as $file => $status) {
            if (trim(substr($status->getStatus(), 0, 1))) {
                $status = CliColor::apply($status->getStatus()
                                                 ->getReadable(), 'green');
            } else {
                $status = CliColor::apply($status->getStatus()
                                                 ->getReadable(), 'red');
            }
            $list[$file] = ['status' => $status];
        }
        Cli::displayTable($list, ['file'   => tr('File'),
                                  'status' => tr('Status'),
        ], 'file');
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_path
     *
     * @return static
     */
    public function patch(string $target_path): static
    {
        try {
            // Add all paths to index, create the patch file, apply it, delete it, done
            $this->getGit()
                 ->add();
            $patch_file = $this->getPatchFile(true);
            $this->getGit()
                 ->reset('HEAD');
            if ($patch_file) {
                Git::new($target_path)
                   ->apply($patch_file);
                File::new($patch_file, Restrictions::new(DIRECTORY_TMP, true))
                    ->delete();
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
                Core::ExecuteNotInTestMode(function () use ($patch_file) {
                    File::new($patch_file, Restrictions::new(DIRECTORY_TMP, true))
                        ->delete();
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
                ]), $e)
                                             ->addData([
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
     * @return string|null
     */
    public function getPatchFile(bool $cached = false): ?string
    {
        return Git::new(dirname($this->directory))
                  ->saveDiff(basename($this->directory), $cached);
    }
}
