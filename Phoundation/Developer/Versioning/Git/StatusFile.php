<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusInterface;
use Phoundation\Os\Processes\Exception\ProcessFailedException;

/**
 * Class StatusFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class StatusFile
{
    /**
     * The file that has a change
     *
     * @var string $file
     */
    protected string $file;

    /**
     * The target in case a file was renamed
     *
     * @var string|null $target
     */
    protected ?string $target = null;

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
     * @param string                 $file
     * @param string                 $target
     */
    public function __construct(StatusInterface|string $status, string $file, string $target)
    {
        $this->file   = $file;
        $this->target = $target;
        $this->status = (is_string($status) ? new Status($status) : $status);
    }


    /**
     * Returns the file name
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }


    /**
     * Returns the target file
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }


    /**
     * Returns the status for this file
     *
     * @return StatusInterface
     */
    public function getStatus(): StatusInterface
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
     * @param string $target_path
     *
     * @return static
     */
    public function patch(string $target_path): static
    {
        try {
            // Create the patch file, apply it, delete it, done
            $patch_file = $this->getPatchFile();
            if ($patch_file) {
                Git::new($target_path)
                   ->apply($patch_file);
//            File::new($patch_file, Restrictions::new(DIRECTORY_TMP, true))->delete();
            }

            return $this;

        } catch (ProcessFailedException $e) {
            $data = $e->getData();
            $data = array_pop($data);
            if (str_contains($data, 'patch does not apply')) {
                throw GitPatchFailedException::new(tr('Failed to apply patch ":patch" to file ":file"', [
                    ':patch' => isset_get($patch_file),
                    ':file'  => $this->file,
                ]))
                                             ->addData([
                                                 'file' => $this->file,
                                             ]);
            }
            throw $e;
        }
    }


    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return string|null
     */
    public function getPatchFile(): ?string
    {
        if ($this->target) {
            return Git::new(dirname($this->target))
                      ->saveDiff(basename($this->target));
        }

        return Git::new(dirname($this->file))
                  ->saveDiff(basename($this->file));
    }


    /**
     * Returns a new Change object
     *
     * @param StatusInterface|string $status
     * @param string                 $file
     * @param string                 $target
     *
     * @return static
     */
    public static function new(StatusInterface|string $status, string $file, string $target): static
    {
        return new static($status, $file, $target);
    }
}
