<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Cli\Color;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchException;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Exception\ProcessFailedException;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * StatusFiles class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
        $this->scanChanges();
    }



    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->list = [];

        $files = $this->git_process
            ->clearArguments()
            ->addArgument('status')
            ->addArgument($this->path)
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

            $this->list[$file] = StatusFile::new($status, $file, $target);
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

        foreach ($this->getList() as $file => $status) {
            if (trim(substr($status->getStatus(), 0, 1))) {
                $status = Color::apply($status->getStatus()->getReadable(), 'green');
            } else {
                $status = Color::apply($status->getStatus()->getReadable(), 'red');
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
            // Create the patch file, apply it, delete it, done
            $patch_file = $this->getPatchFile();

            Git::new($target_path)->apply($patch_file);
            show($target_path);
            showdie($patch_file);
            File::new($patch_file, Restrictions::new(PATH_TMP, true))->delete();

            return $this;

        } catch(ProcessFailedException $e) {
            if (isset($patch_file)) {
                // Delete the temporary patch file
                File::new($patch_file, Restrictions::new(PATH_TMP, true))->delete();
            }

            $data = $e->getData();
            $data = array_pop($data);

            if (str_contains($data, 'patch does not apply')) {
                throw new GitPatchException(tr('Failed to apply patch ":patch" to file ":file"', [
                    ':patch' => isset_get($patch_file),
                    ':file'  => $this->file
                ]));
            }

            throw $e;
        }
    }



    /**
     * Generates a diff patch file for this path and returns the file name for the patch file
     *
     * @return string
     */
    public function getPatchFile(): string
    {
        return Git::new(dirname($this->path))->saveDiff(basename($this->path));
    }



    /**
     * Returns a git object for this path
     *
     * @return Git
     */
    public function getGit(): Git
    {
        if (!isset($this->git)) {
            $this->git = Git::new($this->path);
        }

        return $this->git;
    }
}