<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Exception\GitPatchException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Exception\ProcessFailedException;
use function Phoundation\Versioning\Git\str_contains;


/**
 * Class StatusFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
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
     * @var Status $status
     */
    protected Status $status;



    /**
     * ChangedFile class constructor
     *
     * @param Status|string $status
     * @param string $file
     * @param string $target
     */
    public function __construct(Status|string $status, string $file, string $target)
    {
        $this->file   = $file;
        $this->target = $target;
        $this->status = (is_string($status) ? new Status($status) : $status);
    }



    /**
     * Returns a new Change object
     *
     * @param Status|string $status
     * @param string $file
     * @param string $target
     * @return static
     */
    public static function new(Status|string $status, string $file, string $target): static
    {
        return new static($status, $file, $target);
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
     * @return Status
     */
    public function getStatus(): Status
    {
        return $this->status;
    }



    /**
     * Generates a diff patch file for this file and returns the file name for the patch file
     *
     * @return string
     */
    public function getPatchFile(): string
    {
        if ($this->target) {
            return Git::new(dirname($this->target))->saveDiff(basename($this->target));
        }

        return Git::new(dirname($this->file))->saveDiff(basename($this->file));
    }



    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_path
     * @return static
     */
    public function applyPatch(string $target_path): static
    {
        try {
            // Create the patch file, apply it, delete it, done
            $patch_file = $this->getPatchFile();

            Git::new($target_path)->apply($patch_file);
            File::new($patch_file, Restrictions::new(PATH_TMP, true))->delete();

            return $this;

        }catch(ProcessFailedException $e){
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
}