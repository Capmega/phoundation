<?php

/**
 * Class UploadHandlers
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

use PDOStatement;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataSelector;
use Phoundation\Data\Traits\TraitDataStaticArrayBackup;
use Phoundation\Data\Traits\TraitMethodProcess;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileUploadException;
use Phoundation\Filesystem\Exception\FileUploadHandlerException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFiles;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\FsUploadedFile;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;
use Phoundation\Web\Uploads\Interfaces\UploadHandlersInterface;


class UploadHandlers extends Iterator implements UploadHandlersInterface
{
    use TraitDataStaticArrayBackup;
    use TraitDataSelector;
    use TraitMethodProcess {
        process as protected __process;
    }


    /**
     * Tracks the uploaded files
     *
     * @var FsFilesInterface $files
     */
    protected static FsFilesInterface $files;

    /**
     * Tracks the uploaded files by mime groups
     *
     * @var IteratorInterface $mimetypes_groups
     */
    protected static IteratorInterface $mimetypes_groups;

    /**
     * Tracks the processed files
     *
     * @var FsFilesInterface $processed
     */
    protected static FsFilesInterface $processed;

    /**
     * Tracks the rejected files
     *
     * @var FsFilesInterface $rejected
     */
    protected static FsFilesInterface $rejected;

    /**
     * Tracks if upload failures should be fatal (exception) or just leave log warnings
     *
     * @var bool $exception
     */
    protected bool $exception = false;


    /**
     * UploadHandlers class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);

        $this->setAcceptedDataTypes(UploadHandlerInterface::class);
    }


    /**
     * Link $_POST data to internal arrays to ensure developers cannot access them until validation
     * has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        // Copy FILES data and reset $_FILES
        static::restructureFiles();
        static::groupFiles();
    }


    /**
     * Returns the files that were uploaded
     *
     * @return FsFilesInterface
     */
    public static function getFiles(): FsFilesInterface
    {
        if (empty(static::$files)) {
            throw new OutOfBoundsException(tr('Cannot get files from UploadHandlers object, the $_FILES data has not yet been processed with UploadHandlers::hideData()'));
        }

        return static::$files;
    }


    /**
     * Returns the files that were uploaded
     *
     * @return FsFilesInterface
     */
    public static function getGroupedFiles(): IteratorInterface
    {
        if (empty(static::$mimetypes_groups)) {
            throw new OutOfBoundsException(tr('Cannot get mimetype groups from UploadHandlers object, the $_FILES data has not yet been processed with UploadHandlers::hideData()'));
        }

        return static::$mimetypes_groups;
    }


    /**
     * Groups files into batches by metadata
     *
     * @return void
     */
    protected static function groupFiles(): void
    {
        if (isset(static::$files)) {
            if (static::$files->getCount()) {
                $files                    = clone static::$files;
                static::$mimetypes_groups = Iterator::new()->setAcceptedDataTypes(FsFilesInterface::class);

                while ($files->getCount()) {
                    // Get the mimetype of the first available file, yoink all files with that mimetype out of the list
                    // Redo for each mimetype until no file is left
                    $file      = $files->getFirstValue();
                    $extracted = $files->getFilesWithMimetype($file->getMimetype(), true);

                    static::$mimetypes_groups->add($extracted, $file->getMimetype());
                }
            }
        }
    }


    /**
     * Restructures the internal $_FILES array into static::$files and clears $_FILES
     *
     * @return void
     */
    protected static function restructureFiles(): void
    {
        global $_FILES;

        static::$backup = $_FILES;

        // Check if we get the weird subarrays in name. If so, restructure that mess
        if (count($_FILES)) {
            if (empty($_FILES['file'])) {
                Incident::new()
                    ->setType('Invalid file upload detected')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(tr('Received invalid $_FILES data from client'))
                    ->save()
                    ->throw(ValidationFailedException::class);

            } else {
                if (is_array($_FILES['file']['name'])) {
                    static::$files = static::restructureMultipleFiles($_FILES);

                } else {
                    static::$files = static::restructureSingleFile($_FILES);
                }

                Log::notice(tr('Found ":count" uploaded files', [
                    ':count' => static::$files->getCount()
                ]), 5);
            }
        }

        $_FILES = [];
    }


    /**
     * Restructures a single file in the internal $_FILES array
     *
     * @param array $php_files
     *
     * @return FsFilesInterface
     */
    protected static function restructureSingleFile(array $php_files): FsFilesInterface
    {
        // Register the uploaded file
        $file = Upload::newFromSource($php_files['file'])->save();
        $file = new FsUploadedFile($file);

        // Return a new files object
        return FsFiles::new()
                      ->setAcceptedDataTypes(FsFilesInterface::class)
                      ->setSource([0 => $file]);
    }


    /**
     * Restructures multiple files in the internal $_FILES array
     *
     * @param array $php_files
     *
     * @return FsFilesInterface
     */
    protected static function restructureMultipleFiles(array $php_files): FsFilesInterface
    {
        $files  = [];
        $return = [];

        foreach ($php_files['file'] as $key => $data) {
            foreach ($data as $id => $value) {
                if (empty($files[$id])) {
                    $files[$id] = [];
                }

                $files[$id][$key] = $value;
            }
        }

        // Register the uploaded files
        foreach ($files as $file) {
            $file = Upload::newFromSource($file)->save();
            $file = new FsUploadedFile($file);

            $return[$file->getSource()] = $file;
        }

        // Return a new files object
        return FsFiles::new()
                      ->setAcceptedDataTypes(FsFilesInterface::class)
                      ->setSource($return);
    }


    /**
     * Returns if upload failures will cause exceptions
     *
     * @return bool
     */
    public function getException(): bool
    {
        return $this->exception;
    }


    /**
     * Sets if upload failures will cause exceptions
     *
     * @param bool $exception
     *
     * @return static
     */
    protected function setException(bool $exception): static
    {
        $this->exception = $exception;
        return $this;
    }


    /**
     * Will process the file uploads
     *
     * @return static
     */
    public function process(): static
    {
        if ($this->isProcessed()) {
            throw new FileUploadException(tr('Cannot process file uploads, the file uploads have already been processed'));
        }

        if (Request::isPostRequestMethod()) {
            if (empty($this->source)) {
                throw new FileUploadHandlerException(tr('Cannot process uploaded files, no upload handlers have been defined'));
            }

            if (isset(static::$mimetypes_groups)) {
                // Fix the mimetype groups store to match the handlers, validate the files before processing
                $this->fixMimetypeGroups();
                $this->validate();

                Log::action(tr('Processing ":count" uploaded files with ":handlers" out of ":max" handlers', [
                    ':count'    => static::$files->getCount(),
                    ':max'      => $this->getCount(),
                    ':handlers' => static::$mimetypes_groups->getCount(),
                ]));

                foreach (static::$mimetypes_groups as $mimetype => $files) {
                    $handler = $this->getHandlerForMimetype($mimetype);

                    foreach ($files as $file) {
                        try {
                            Log::action(tr('Processing uploaded file ":file"', [
                                ':file' => $file->getBasename()
                            ]), 4);

                            $handler->process($file);

                        } catch (FileUploadException $e) {
                            if ($this->exception) {
                                throw $e;
                            }

                            // Register the incident in text & developer incidents log
                            $incident = Incident::new()
                                ->setSeverity(EnumSeverity::low)
                                ->setTitle($e->getMessage())
                                ->setDetails([
                                    'file'      => $file,
                                    'exception' => $e
                                ])
                                ->save();
                        }
                    }
                }

                if (isset($incident)) {
                    // One or more of the files failed! Throw an exception for the last one
                    $incident->throw(FileUploadException::class);
                }
            }
        }

        // Set the is_processed flag
        $this->__process();
        return $this;
    }


    /**
     * Fixes the mimetype groups
     *
     * The static::$mimetypes_groups has mimetype keys based off the files, whereas processing will be with the
     * mimetypes from the handlers. Handlers may have partial mimetypes like "image" instead of "image/jpeg". This
     * function will correct the mimetype name from the file "image/jpeg" to the handler's "image" as is required later
     *
     * @return void
     */
    protected function fixMimetypeGroups(): void
    {
        foreach (static::$mimetypes_groups as $mimetype => $group) {
            $handler = $this->getHandlerForMimetype($mimetype);

            if ($handler->getDropZoneObject()->getMimetype() !== $mimetype) {
                static::$mimetypes_groups->renameKey($mimetype, $handler->getDropZoneObject()->getMimetype());
            }
        }
    }


    /**
     * Validates the entire upload
     *
     * @return $this
     */
    protected function validate(): static
    {
        $mimetypes = $this->getMimetypeRestrictions();

        // Ensure we don't have too many files uploaded per mimetype
        foreach ($mimetypes as $mimetype => $restrictions) {
            if (!static::$mimetypes_groups->keyExists($mimetype)) {
                // No file with this mimetype was uploaded
                if ($restrictions['min_files']) {
                    throw new ValidationFailedException(tr('No files uploaded with the mimetype ":mimetype", while at least ":min" file(s) are required', [
                        ':mimetype' => $mimetype,
                        ':min'      => $restrictions['min_files']
                    ]));
                }
            }

            if (static::$mimetypes_groups->get($mimetype)->getCount() < $restrictions['min_files']) {
                throw new ValidationFailedException(tr('Too few files ":count" uploaded with the mimetype ":mimetype", at least ":min" file(s) are required', [
                    ':mimetype' => $mimetype,
                    ':count'    => static::$mimetypes_groups->get($mimetype)->getCount(),
                    ':min'      => $restrictions['min_files']
                ]));
            }

            if (static::$mimetypes_groups->get($mimetype)->getCount() > $restrictions['max_files']) {
                throw new ValidationFailedException(tr('Too many files ":count" uploaded with the mimetype ":mimetype", only ":max" file(s) are allowed', [
                    ':mimetype' => $mimetype,
                    ':count'    => static::$mimetypes_groups->get($mimetype)->getCount(),
                    ':max'      => $restrictions['max_files']
                ]));
            }
        }

        return $this->validateFiles();
    }


    /**
     * Validates each uploaded file individually
     *
     * @return $this
     */
    protected function validateFiles(): static
    {
        $failures  = [];

        Log::action(tr('Validating ":count" uploaded files', [
            ':count' => static::$files->getCount()
        ]), 4);

        foreach (static::$mimetypes_groups as $mimetype => $files) {
            try {
                $handler = $this->getHandlerForMimetype($mimetype);

                foreach ($files as $file) {
                    try {
                        $handler->validate($file);

                    } catch (ValidationFailedException $e) {
                        // Add all failures together
                        $failures[] = $e;
                    }
                }
            } catch (ValidationFailedException $e) {
                // Add all failures together
                $failures[] = $e;
            }
        }

        if (count($failures)) {
            // Validation failed! Gather all failure data and throw it in one exception
            $data = [];

            foreach ($failures as $failure) {
                $data[] = $failure->getDataKey('failures');
            }

            throw ValidationFailedException::new(tr('Validation of ":count" uploaded files failed', [
                ':count' => count(static::$files)
            ]))->setData($data);
        }

        return $this;
    }


    /**
     * Returns a list of mimetypes and their restrictions
     *
     * @return array
     */
    protected function getMimetypeRestrictions(): array
    {
        $return = [];

        foreach ($this->source as $handler) {
            $dropzone = $handler->getDropzoneObject();

            $return[$handler->getDropZoneObject()->getMimetype()] = [
                'min_files' => $dropzone->getMaxFiles(),
                'max_files' => $dropzone->getMaxFiles(),
                'max_size'  => $dropzone->getMaxFileSize()
            ];
        }

        return $return;
    }


    /**
     * Returns the handler for the specified file mimetype if available, throws an FileUploadHandlerException if not
     *
     * @param FsUploadedFileInterface $file
     *
     * @return UploadHandlerInterface
     */
    protected function getHandlerForFile(FsUploadedFileInterface $file): UploadHandlerInterface
    {
        try {
            return $this->getHandlerForMimetype($file->getMimetype());

        } catch (FileUploadHandlerException $e) {
            throw new FileUploadHandlerException(tr('Cannot process uploaded file ":file" with size ":size" and mimetype ":mimetype", it has no mimetype handler specified', [
                ':file'     => $file->getRealName(),
                ':size'     => $file->getSize(),
                ':mimetype' => $file->getMimetype(),
            ]), $e);
        }
    }


    /**
     * Returns the handler for the specified file mimetype if available, throws an FileUploadHandlerException if not
     *
     * @param string $mimetype
     *
     * @return UploadHandlerInterface
     */
    protected function getHandlerForMimetype(string $mimetype): UploadHandlerInterface
    {
        foreach ($this->source as $source_mimetype => $handler) {
            if (str_starts_with($mimetype, $source_mimetype)) {
                break;
            }

            unset($handler);
        }

        if (empty($handler)) {
            throw new ValidationFailedException(tr('No handler found for mimetype ":mimetype"', [
                ':mimetype' => $mimetype,
            ]));
        }

        return $handler;
    }


    /**
     * Renders and returns the upload handler code
     *
     * @return string|null
     */
    public function render(): ?string
    {
        Response::loadJavascript('vendor/dropzone/dropzone');
        $render = null;

        foreach ($this->source as $handler) {
            $render .= $handler->render();
        }

        return $render;
    }


    /**
     * Fetches a mimetype key from the UploadHandler object
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function fetchKeyFromValue(mixed $value): mixed
    {
        return $value->getDropzoneObject()->getMimetype();
    }


    /**
     * Returns the files that were uploaded
     *
     * @return IteratorInterface
     */
    public function getUploadedFiles(): IteratorInterface
    {
        return new Iterator(static::$files);
    }


    /**
     * Returns the files that were processed
     *
     * @return FsFilesInterface
     */
    public function getProcessedFiles(): FsFilesInterface
    {
        return static::$processed;
    }


    /**
     * Returns the files that were rejected
     *
     * @return IteratorInterface
     */
    public function getRejectedFiles(): IteratorInterface
    {
        return new Iterator(static::$rejected);
    }


    /**
     * Returns if any files were uploaded
     *
     * @return bool
     */
    public function hasUploadedFiles(): bool
    {
        if (empty(static::$files)) {
            return false;
        }

        return static::$files->isNotEmpty();
    }


    /**
     * Returns if any files were processed
     *
     * @return bool
     */
    public function hasProcessedFiles(): bool
    {
        if (empty(static::$processed)) {
            return false;
        }

        return static::$processed->isNotEmpty();
    }


    /**
     * Returns if any files were rejected
     *
     * @return bool
     */
    public function hasRejectedFiles(): bool
    {
        return static::$rejected->isNotEmpty();
    }


    /**
     * This method will move all files to a quarantined location for further review later
     *
     * @return $this
     */
    protected function quarantineAllUploadedFiles(): static
    {
        $directory = new FsDirectory(DIRECTORY_DATA . 'quarantine', FsRestrictions::newData());
        $directory = $directory->addDirectory(Session::getUserObject()->getId());
        $directory = $directory->addDirectory(DateTime::new()->format('file'));

        foreach (static::$files as $file) {
            $file->move($directory);
        }

        return $this;
    }
}
