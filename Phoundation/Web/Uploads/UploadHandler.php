<?php

/**
 * Class UploadHandler
 *
 * This request subclass handles upload functionalities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Uploads;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoUploadedFileInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Uploads\Interfaces\DropzoneInterface;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;


class UploadHandler implements UploadHandlerInterface
{
    /**
     * The handler code for this file
     *
     * @var mixed|null
     */
    protected mixed $function = null;

    /**
     * Validations to execute to ensure
     */
    protected array $validations = [];

    /**
     * The files that have been processed by this upload handler
     *
     * @var PhoFilesInterface $files
     */
    protected PhoFilesInterface $files;

    /**
     * Tracks if the file in this handler has been validated or not
     *
     * @var bool $validated
     */
    protected bool $validated = false;

    /**
     * The dropzone object for this upload handler
     *
     * @var DropzoneInterface $dropzone
     */
    protected DropzoneInterface $dropzone;


    /**
     * UploadHandler class constructor
     */
    public function __construct(?string $mimetype = null)
    {
        $this->getDropZoneObject()->setMimetype($mimetype)
                                  ->setUrl(Url::getWww());
    }


    /**
     * Returns a new UploadHandler class
     */
    public static function new(?string $mimetype = null)
    {
        return new static($mimetype);
    }


    /**
     * Returns a list of all processed files
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface
    {
        if (empty($this->files)) {
            $this->files = new PhoFiles();
            $this->files->setAcceptedDataTypes(PhoUploadedFileInterface::class)
                        ->getRestrictions()
                            ->addDirectory(DIRECTORY_TMP, true)
                            ->addDirectory('/tmp/', true);
        }

        return $this->files;
    }


    /**
     * Returns the current number of files that have been processed
     *
     * @return int
     */
    public function getFileNumber(): int
    {
        return $this->getFiles()->getCount();
    }


    /**
     * Returns the current number of files that have been processed
     *
     * @return DropzoneInterface
     */
    public function getDropZoneObject(): DropzoneInterface
    {
        if (empty($this->dropzone)) {
            $this->dropzone = new Dropzone($this);
        }

        return $this->dropzone;
    }


    /**
     * Returns the handler function for this file
     *
     * @return callable
     */
    public function getFunction(): callable
    {
        return $this->function;
    }


    /**
     * Sets the handler function for this file
     *
     * @param callable $function
     *
     * @return static
     */
    public function setFunction(callable $function): static
    {
        Request::getMethodRestrictionsObject()->allow(EnumHttpRequestMethod::upload);

        $this->function = $function;

        return $this;
    }


    /**
     * Clears all currently existing validation functions for this definition
     *
     * @return static
     */
    public function clearValidationFunctions(): static
    {
        $this->validations = [];

        return $this;
    }


    /**
     * Adds the specified validation function to the validation functions list for this definition
     *
     * @param callable $function
     *
     * @return static
     */
    public function addValidationFunction(callable $function): static
    {
        $this->validations[] = $function;

        return $this;
    }


    /**
     * Renders the drag/drop code for this handler, if needed
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return $this->getDropZoneObject()->render();
    }


    /**
     * Have this handler process the specified uploaded file
     *
     * @param PhoUploadedFileInterface $file
     *
     * @return PhoUploadedFileInterface
     */
    public function process(PhoUploadedFileInterface $file): PhoUploadedFileInterface
    {
        // Check if somehow we already processed more than the maximum indicated
        if ($this->getFiles()->getCount() > $this->getDropZoneObject()->getMaxFiles()) {
            throw ValidationFailedException::new(tr('This mimetype ":mimetype" upload handler already processed the maximum amount of files ":count" that it is allowed to process', [
                ':count'    => $this->getDropZoneObject()->getMaxFiles(),
                ':mimetype' => $this->getDropZoneObject()->getMimetype()
            ]))->registerIncident(EnumSeverity::medium);
        }

        Log::action(tr('About to process ":count" files with mimetype ":mimetype"', [
            ':count'    => $this->getDropZoneObject()->getMaxFiles(),
            ':mimetype' => $this->getDropZoneObject()->getMimetype()
        ]));

        $this->validate($file);

        if (!$this->hasBeenValidated()) {
            throw new ValidationFailedException(tr('Cannot start processing for file ":file" with mimetype ":mimetype", the file has not been validated', [
                ':file'     => $file->getSource(),
                ':mimetype' => $file->getMimetype()
            ]));
        }

        if ($this->function) {
            ($this->function)($file);
        }

        $this->getFiles()->add($file);

        return $file;
    }


    /**
     * Returns true if the file in this handler has been validated
     *
     * @return bool
     */
    public function hasBeenValidated(): bool
    {
        return $this->validated;
    }


    /**
     * Have this handler process the specified uploaded file
     *
     * @param PhoUploadedFileInterface $file
     *
     * @return PhoUploadedFileInterface
     */
    public function validate(PhoUploadedFileInterface $file): PhoUploadedFileInterface
    {
        // Ensure the file has the correct extension
        $file->ensureExtensionMatchesMimetype();

//        $validator = FileValidator::new($file);
//
//        foreach ($this->validations as $function) {
//            $function($validator);
//        }
//
//        $validator->validate();

        $this->validated = true;
        return $file;
    }
}
