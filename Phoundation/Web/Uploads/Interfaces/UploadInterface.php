<?php

namespace Phoundation\Web\Uploads\Interfaces;

use Phoundation\Web\Uploads\Upload;


interface UploadInterface
{
    /**
     * Returns the name for this uploaded file
     *
     * @return string|null
     */
    public function getName(): ?string;


    /**
     * Returns the full path for this uploaded file
     *
     * @return string|null
     */
    public function getFullPath(): ?string;


    /**
     * Returns the tmp_name (The local file name in /tmp when it was uploaded to PHP) for this uploaded file
     *
     * @return string|null
     */
    public function getTmpName(): ?string;


    /**
     * Returns the type for this uploaded file
     *
     * @return string|null
     */
    public function getType(): ?string;


    /**
     * Returns the size for this uploaded file
     *
     * @return int|null
     */
    public function getSize(): ?int;


    /**
     * Returns the error_code for this uploaded file
     *
     * @return int|null
     */
    public function getError(): ?int;


    /**
     * Returns the hash for this uploaded file
     *
     * @return string|null
     */
    public function getHash(): ?string;


    /**
     * If we have a tmp_name, we must have a hash!
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return $this
     */
    public function save(bool $force = false, ?string $comments = null): static;
}
