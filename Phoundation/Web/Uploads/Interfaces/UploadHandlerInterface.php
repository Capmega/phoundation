<?php

declare(strict_types=1);

namespace Phoundation\Web\Uploads\Interfaces;

use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;


interface UploadHandlerInterface
{
    /**
     * Returns the current number of files that have been processed
     *
     * @return int
     */
    public function getFileNumber(): int;

    /**
     * Returns the current number of files that have been processed
     *
     * @return DropzoneInterface
     */
    public function getDropZoneObject(): DropzoneInterface;

    /**
     * Returns the handler function for this file
     *
     * @return callable
     */
    public function getFunction(): callable;

    /**
     * Sets the handler function for this file
     *
     * @param callable $function
     *
     * @return static
     */
    public function setFunction(callable $function): static;

    /**
     * Clears all currently existing validation functions for this definition
     *
     * @return static
     */
    public function clearValidationFunctions(): static;

    /**
     * Adds the specified validation function to the validation functions list for this definition
     *
     * @param callable $function
     *
     * @return static
     */
    public function addValidationFunction(callable $function): static;

    /**
     * Renders the drag/drop code for this handler, if needed
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Have this handler process the specified uploaded file
     *
     * @param FsUploadedFileInterface $file
     *
     * @return FsUploadedFileInterface
     */
    public function process(FsUploadedFileInterface $file): FsUploadedFileInterface;

    /**
     * Returns true if the file in this handler has been validated
     *
     * @return bool
     */
    public function hasBeenValidated(): bool;

    /**
     * Have this handler process the specified uploaded file
     *
     * @param FsUploadedFileInterface $file
     *
     * @return FsUploadedFileInterface
     */
    public function validate(FsUploadedFileInterface $file): FsUploadedFileInterface;
}
