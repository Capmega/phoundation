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
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;

    /**
     * Returns the Comments for this uploaded file
     *
     * @return string|null
     */
    public function getComments(): ?string;

    /**
     * Sets the Comments for this uploaded file
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function addComment(?string $comments): static;

    /**
     * Sets the Comments for this uploaded file
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function setComments(?string $comments): static;

    /**
     * Returns the error message for the specified error code
     *
     * @param int|null $error
     *
     * @return string
     */
    public function getUploadErrorMessage(?int $error = null): string;
}
