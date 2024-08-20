<?php

/**
 * Class StatusFile
 *
 * FsFile extended object containing git status information about that file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFileInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusInterface;
use Phoundation\Filesystem\FsFileCore;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


class StatusFile extends FsFileCore implements StatusFileInterface
{
    /**
     * The file that has a change
     *
     * @var FsFileInterface $file
     */
    protected FsFileInterface $file;

    /**
     * The target in case a file was renamed
     *
     * @var FsFileInterface|null $git_target
     */
    protected ?FsFileInterface $git_target = null;

    /**
     * The status for this file
     *
     * @var StatusInterface $status
     */
    protected StatusInterface $status;


    /**
     * ChangedFile class constructor
     *
     * @param StatusInterface|string $status
     * @param FsFileInterface        $file
     * @param FsFileInterface        $git_target
     */
    public function __construct(StatusInterface|string $status, FsFileInterface $file, FsFileInterface $git_target)
    {
        $this->file       = $file;
        $this->git_target = $git_target;
        $this->status     = (is_string($status) ? new Status($status) : $status);
    }


    /**
     * Returns a new StatusFile object
     *
     * @param StatusInterface|string $status
     * @param FsFileInterface        $file
     * @param FsFileInterface        $git_target
     *
     * @return static
     */
    public static function new(StatusInterface|string $status, FsFileInterface $file, FsFileInterface $git_target): static
    {
        return new static($status, $file, $git_target);
    }


    /**
     * Returns the file name
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface
    {
        return $this->file;
    }


    /**
     * Returns the target file
     *
     * @return FsFileInterface|null
     */
    public function getGitTarget(): ?FsFileInterface
    {
        return $this->git_target;
    }


    /**
     * Returns the status for this file
     *
     * @return StatusInterface
     */
    public function getStatusObject(): StatusInterface
    {
        return $this->status;
    }


    /**
     * Returns true if this file has a git conflict
     *
     * @return bool
     */
    public function hasConflict(): bool
    {
        return $this->status->isConflict();
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param FsPathInterface $target_path
     *
     * @return static
     */
    public function patch(FsPathInterface $target_path): static
    {
        try {
            // Create the patch file, apply it, delete it, done
            $patch_file = $this->getPatchFile();

            if ($patch_file) {
                Git::new($target_path)->apply($patch_file);

                $patch_file->delete();
            }

            return $this;

        } catch (ProcessFailedException $e) {
            $data = $e->getData();
            $data = array_pop($data);

            if (str_contains($data, 'patch does not apply')) {
                throw GitPatchFailedException::new(tr('Failed to apply patch ":patch" to file ":file"', [
                    ':patch' => isset_get($patch_file),
                    ':file'  => $this->file,
                ]))->addData([
                    'file' => $this->file,
                ]);
            }

            throw $e;
        }
    }


    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return FsFileInterface
     */
    public function getPatchFile(): FsFileInterface
    {
        if ($this->git_target) {
            return Git::new($this->git_target->getParentDirectory())
                      ->saveDiff($this->git_target->getBasename());
        }

        return Git::new($this->file->getParentDirectory())
                  ->saveDiff($this->file->getBasename());
    }
}
