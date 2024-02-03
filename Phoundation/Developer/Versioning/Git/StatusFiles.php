<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchException;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Utils\Strings;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class StatusFiles extends Iterator
{
    use GitProcess;


    /**
     * A git object specifically for this path
     *
     * @var Git $git
     */
    protected Git $git;


    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->source = [];

        $files = $this->git_process
            ->clearArguments()
            ->addArgument('status')
            ->addArgument($this->directory)
            ->addArgument('--porcelain')
            ->executeReturnArray();

        // Parse output
        foreach ($files as $file) {
            $status = substr($file, 0, 2);
            $file   = substr($file, 3);
            $target = '';

            if (str_contains($file, ' -> ')) {
                $target = Strings::from($file, ' -> ');
                $file   = Strings::until($file, ' -> ');
            }

            $this->source[$file] = StatusFile::new($status, $file, $target);
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
                $status = CliColor::apply($status->getStatus()->getReadable(), 'green');
            } else {
                $status = CliColor::apply($status->getStatus()->getReadable(), 'red');
            }
            $list[$file] = ['status' => $status];
        }

        Cli::displayTable($list, ['file' => tr('File'), 'status' => tr('Status')], 'file');
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_path
     * @return static
     */
    public function patch(string $target_path): static
    {
        try {
            // Add all paths to index, create the patch file, apply it, delete it, done
            $this->getGit()->add();

            $patch_file = $this->getPatchFile(true);

            $this->getGit()->reset('HEAD');

            if ($patch_file) {
                Git::new($target_path)->apply($patch_file);
                File::new($patch_file, Restrictions::new(DIRECTORY_TMP, true))->delete();
            }

            return $this;

        } catch(ProcessFailedException $e) {
            Log::warning(tr('Patch failed to apply for target directory ":directory" with following exception', [
                ':directory' => $target_path
            ]));

            Log::warning($e->getMessages());
            Log::warning($e->getDataKey('output'));

            if (isset($patch_file)) {
                // Delete the temporary patch file
                Core::ExecuteNotInTestMode(function() use ($patch_file) {
                    File::new($patch_file, Restrictions::new(DIRECTORY_TMP, true))->delete();
                }, tr('Removing git patch files'));
            }

            $data = $e->getData();
            $data = $data['output'];
            $data = array_pop($data);

            if (str_contains($data, 'patch does not apply')) {
                $file = Strings::cut($data, 'error: ', ': patch does not apply');

                throw GitPatchException::new(tr('Failed to apply patch ":patch" to directory ":directory"', [
                    ':patch'     => isset_get($patch_file),
                    ':directory' => $target_path
                ]))->addData([
                    ':file' => $file
                ]);
            }

            throw $e;
        }
    }


    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @param bool $cached
     * @return string|null
     */
    public function getPatchFile(bool $cached = false): ?string
    {
        return Git::new(dirname($this->directory))->saveDiff(basename($this->directory), $cached);
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
}
