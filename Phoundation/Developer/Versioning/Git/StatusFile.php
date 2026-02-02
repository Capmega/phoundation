<?php

/**
 * Class StatusFile
 *
 * PhoFile extended object containing git status information about that file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFileInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusInterface;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectRepository;
use Phoundation\Filesystem\PhoFileCore;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;


class StatusFile extends PhoFileCore implements StatusFileInterface
{
    use TraitDataObjectRepository;


    /**
     * The target in case a file was renamed
     *
     * @var PhoFileInterface|null $git_target
     */
    protected ?PhoFileInterface $git_target = null;

    /**
     * The status for this file
     *
     * @var StatusInterface $o_status
     */
    protected StatusInterface $o_status;


    /**
     * ChangedFile class constructor
     *
     * @param StatusInterface|string $status
     * @param PhoFileInterface       $file
     * @param PhoFileInterface|null  $git_target
     */
    public function __construct(StatusInterface|string $status, PhoFileInterface $file, ?PhoFileInterface $git_target = null)
    {
        $this->source     = $file->getSource();
        $this->git_target = $git_target;
        $this->o_status   = (is_string($status) ? new Status($status) : $status);
    }


    /**
     * Returns a new StatusFile object
     *
     * @param StatusInterface|string $status
     * @param PhoFileInterface       $file
     * @param PhoFileInterface|null  $git_target
     *
     * @return static
     */
    public static function new(StatusInterface|string $status, PhoFileInterface $file, ?PhoFileInterface $git_target = null): static
    {
        return new static($status, $file, $git_target);
    }


    /**
     * Returns the target file
     *
     * @return PhoFileInterface|null
     */
    public function getGitTarget(): ?PhoFileInterface
    {
        return $this->git_target;
    }


    /**
     * Returns the status object for this file
     *
     * @return StatusInterface
     */
    public function getStatusObject(): StatusInterface
    {
        return $this->o_status;
    }


    /**
     * Returns the status for this file
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->o_status->getStatus();
    }


    /**
     * Returns the status for this file
     *
     * @return string
     */
    public function getReadableStatus(): string
    {
        return $this->o_status->getReadable();
    }


    /**
     * Returns true if this file has a git conflict
     *
     * @return bool
     */
    public function hasConflict(): bool
    {
        return $this->o_status->isConflict();
    }


    /**
     * Applies the patch for this file on the specified target file
     *
     * @param PhoPathInterface $target_path
     *
     * @return static
     */
    public function patch(PhoPathInterface $target_path): static
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
                    ':file'  => $this->source,
                ]))->addData([
                    'file' => $this->source,
                ]);
            }

            throw $e;
        }
    }


    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return PhoFileInterface
     */
    public function getPatchFile(): PhoFileInterface
    {
        if ($this->git_target) {
            return Git::new($this->git_target->getParentDirectoryObject())
                      ->saveDiff($this->git_target->getBasename());
        }

        return Git::new($this->getParentDirectoryObject())
                  ->saveDiff($this->getBasename());
    }
}
