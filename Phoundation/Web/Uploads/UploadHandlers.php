<?php

/**
 * Class UploadHandlers
 *
 * This request subclass handles upload functionalities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Uploads;

use PDOStatement;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataSelector;
use Phoundation\Data\Traits\TraitDataStaticArrayBackup;
use Phoundation\Data\Traits\TraitMethodProcess;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileUploadException;
use Phoundation\Filesystem\Exception\FileUploadHandlerException;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\PhoUploadedFile;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;
use Phoundation\Web\Uploads\Interfaces\UploadHandlersInterface;
use Stringable;

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
     * @var PhoFilesInterface $files
     */
    protected static PhoFilesInterface $files;

    /**
     * Tracks the uploaded files by mime groups
     *
     * @var IteratorInterface $mimetypes_groups
     */
    protected static IteratorInterface $mimetypes_groups;

    /**
     * Tracks the processed files
     *
     * @var PhoFilesInterface $processed
     */
    protected static PhoFilesInterface $processed;

    /**
     * Tracks the rejected files
     *
     * @var PhoFilesInterface $rejected
     */
    protected static PhoFilesInterface $rejected;

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
     * @return PhoFilesInterface
     */
    public static function getFiles(): PhoFilesInterface
    {
        if (empty(static::$files)) {
            throw new OutOfBoundsException(tr('Cannot get files from UploadHandlers object, the $_FILES data has not yet been processed with UploadHandlers::hideData()'));
        }

        return static::$files;
    }


    /**
     * Returns the files that were uploaded
     *
     * @return PhoFilesInterface
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
                static::$mimetypes_groups = Iterator::new()->setAcceptedDataTypes(PhoFilesInterface::class);

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

                Log::notice(ts('Found ":count" uploaded files', [
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
     * @return PhoFilesInterface
     */
    protected static function restructureSingleFile(array $php_files): PhoFilesInterface
    {
        // Register the uploaded file
        $file = Upload::newFromSource($php_files['file'])->save();
        $file = new PhoUploadedFile($file);

        // Return a new files object
        return PhoFiles::new()
                       ->setAcceptedDataTypes(PhoFilesInterface::class)
                       ->setSource([0 => $file]);
    }


    /**
     * Restructures multiple files in the internal $_FILES array
     *
     * @param array $php_files
     *
     * @return PhoFilesInterface
     */
    protected static function restructureMultipleFiles(array $php_files): PhoFilesInterface
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
            $file = new PhoUploadedFile($file);

            $return[$file->getSource()] = $file;
        }

        // Return a new files object
        return PhoFiles::new()
                       ->setAcceptedDataTypes(PhoFilesInterface::class)
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

                Log::action(ts('Processing ":count" uploaded files with ":handlers" out of ":max" handlers', [
                    ':count'    => static::$files->getCount(),
                    ':max'      => $this->getCount(),
                    ':handlers' => static::$mimetypes_groups->getCount(),
                ]));

                foreach (static::$mimetypes_groups as $mimetype => $files) {
                    $handler = $this->getHandlerForMimetype($mimetype, $files);

                    foreach ($files as $file) {
                        try {
                            Log::action(ts('Processing uploaded file ":file" with mimetype handler ":handler"', [
                                ':file'    => $file->getBasename(),
                                ':handler' => get_class($handler)
                            ]), 4);

                            $handler->process($file);

                        } catch (FileUploadException $e) {
                            if ($this->exception) {
                                throw $e;
                            }

                            // Register the incident in text & developer incidents log
                            $incident = Incident::new()
                                                ->setType('File upload processing failed')
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
            $handler_mimetype = $this->getHandlerKeyForMimetype($mimetype, $group);

            if ($handler_mimetype !== $mimetype) {
                static::$mimetypes_groups->renameKey($mimetype, $handler_mimetype);
            }
        }
    }


    /**
     * Validates the entire upload
     *
     * @return static
     */
    protected function validate(): static
    {
        $mimetype_restrictions = $this->getMimetypeRestrictions();

        // Ensure we don't have too many files uploaded per mimetype
        foreach ($mimetype_restrictions as $mimetypes => $restrictions) {
            if (!static::$mimetypes_groups->keyExists($mimetypes)) {
                // No file with this mimetype was uploaded
                if ($restrictions['min_files']) {
                    throw new ValidationFailedException(tr('No files uploaded with the mimetype ":mimetype", while at least ":min" file(s) are required', [
                        ':mimetype' => $mimetypes,
                        ':min'      => $restrictions['min_files']
                    ]));
                }
            }

            if (static::$mimetypes_groups->get($mimetypes)->getCount() < $restrictions['min_files']) {
                throw new ValidationFailedException(tr('Too few files ":count" uploaded with the mimetype ":mimetype", at least ":min" file(s) are required', [
                    ':mimetype' => $mimetypes,
                    ':count'    => static::$mimetypes_groups->get($mimetypes)->getCount(),
                    ':min'      => $restrictions['min_files']
                ]));
            }

            if (static::$mimetypes_groups->get($mimetypes)->getCount() > $restrictions['max_files']) {
                throw new ValidationFailedException(tr('Too many files ":count" uploaded with the mimetype ":mimetype", only ":max" file(s) are allowed', [
                    ':mimetype' => $mimetypes,
                    ':count'    => static::$mimetypes_groups->get($mimetypes)->getCount(),
                    ':max'      => $restrictions['max_files']
                ]));
            }
        }

        return $this->validateFiles();
    }


    /**
     * Returns an Iterator object containing all mimetype groups, grouped by iterator mimetype group
     *
     * @return IteratorInterface
     */
    protected function getHandlersFiles(): IteratorInterface
    {
        $return = [];

        foreach (static::$mimetypes_groups as $mimetype => $group) {
            $handler_mimetype = $this->getHandlerKeyForMimetype($mimetype, $group);

            if (array_key_exists($handler_mimetype, $return)) {
                $return[$handler_mimetype]->addSource($group);

            } else {
                $return[$handler_mimetype] = $group;
            }
        }

        return new Iterator($return);
    }


    /**
     * Validates each uploaded file individually
     *
     * @return static
     * @todo implement file validations!
     */
    protected function validateFiles(): static
    {
        $failures  = [];

        Log::action(ts('Validating ":count" uploaded files', [
            ':count' => static::$files->getCount()
        ]), 4);

        foreach (static::$mimetypes_groups as $mimetype => $files) {
            try {
                $handler = $this->getHandlerForMimetype($mimetype, $files);

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
throw new UnderConstructionException(tr('IMPLEMENT FILE VALIDATIONS'));
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

        foreach ($this->source as $mimetype => $handler) {
            $dropzone = $handler->getDropzoneObject();

            $return[$mimetype] = [
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
     * @param string            $mimetype
     * @param PhoFilesInterface $files
     *
     * @return UploadHandlerInterface
     */
    protected function getHandlerForMimetype(string $mimetype, PhoFilesInterface $files): UploadHandlerInterface
    {
        foreach ($this->source as $source_mimetype => $handler) {
            if (str_starts_with($mimetype, $source_mimetype)) {
                break;
            }

            unset($handler);
        }

        if (empty($handler)) {
            throw ValidationFailedException::new(tr('No handler found for files ":files" with mimetype ":mimetype"', [
                ':mimetype' => $mimetype,
                ':files'    => Strings::force($files->getBasenames(), ', ')
            ]))->setData([
                'files' => $files
            ]);
        }

        return $handler;
    }


    /**
     * Returns the handler for the specified file mimetype if available, throws an FileUploadHandlerException if not
     *
     * @param string            $mimetype
     * @param PhoFilesInterface $files
     *
     * @return string
     */
    protected function getHandlerKeyForMimetype(string $mimetype, PhoFilesInterface $files): string
    {
        foreach ($this->source as $source_mimetypes => $handler) {
            $test_types = explode(',', $source_mimetypes);

            foreach ($test_types as $test_type) {
                if (str_starts_with($mimetype, $test_type)) {
                    break 2;
                }
            }

            unset($source_mimetypes);
        }

        if (empty($source_mimetypes)) {
            throw ValidationFailedException::new(tr('No handler found for files ":files" with mimetype ":mimetype"', [
                ':mimetype' => $mimetype,
                ':files'    => Strings::force($files->getBasenames(), ', ')
            ]))->setData([
                'files' => $files
            ]);
        }

        return $source_mimetypes;
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
     * Add the specified UploadHandler object to the iterator array using an optional key
     *
     * @note The key for this Iterator list is (MUST BE) the mimetype of the UploadHandler object. If multiple mimetypes
     *       are specified, these will automatically be converted into single mimetypes, each with the same
     *       UploadHandler
     *
     *
     * @param mixed                      $value
     * @param Stringable|string|float|int|null $key
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        if (empty($key)) {
            $key = $value->getDropzoneObject()->getMimetypes();
            $key = Strings::force($key);
        }

        return parent::append($value, $key, $skip_null_values, $exception);
    }


    /**
     * Add the specified value to the iterator array using an optional key
     *
     * @note if no key was specified, the entry will be assigned as-if a new array entry
     *
     * @param mixed                      $value
     * @param Stringable|string|float|int|null $key
     * @param bool                       $skip_null_values
     * @param bool                       $exception
     *
     * @return static
     */
    public function prepend(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        if (empty($key)) {
            $key = $value->getDropzoneObject()->getMimetypes();
            $key = Strings::force($key);
        }

        return parent::prepend($value, $key, $skip_null_values, $exception);
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
     * @return PhoFilesInterface
     */
    public function getProcessedFiles(): PhoFilesInterface
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
     * @return static
     */
    protected function quarantineAllUploadedFiles(): static
    {
        $directory = new PhoDirectory(DIRECTORY_DATA . 'quarantine', PhoRestrictions::newDataObject());
        $directory = $directory->addDirectory(Session::getUserObject()->getId());
        $directory = $directory->addDirectory(PhoDateTime::new()->format('file'));

        foreach (static::$files as $file) {
            $file->move($directory);
        }

        return $this;
    }
}
