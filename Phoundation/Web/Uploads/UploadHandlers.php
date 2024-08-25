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
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataSelector;
use Phoundation\Data\Traits\TraitMethodProcess;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Filesystem\Exception\FileUploadException;
use Phoundation\Filesystem\Exception\FileUploadHandlerException;
use Phoundation\Filesystem\FsUploadedFile;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;
use Phoundation\Web\Uploads\Interfaces\UploadHandlersInterface;


class UploadHandlers extends Iterator implements UploadHandlersInterface
{
    use TraitDataSelector;
    use TraitMethodProcess {
        process as protected __process;
    }


    /**
     * Tracks the uploaded files
     *
     * @var array $files
     */
    protected static array $files;

    /**
     * Tracks the processed files
     *
     * @var array $processed
     */
    protected static array $processed;

    /**
     * Tracks the rejected files
     *
     * @var array $rejected
     */
    protected static array $rejected;

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
        global $_FILES;

        // Copy FILES data and reset $_FILES
        static::$files = $_FILES;
        static::restructureFiles();
        $_FILES = [];
    }


    /**
     * Restructures the internal $_FILES array
     *
     * @return void
     */
    protected static function restructureFiles(): void
    {
        // Check if we get the weird subarrays in name. If so, restructure that mess
        if (count(static::$files)) {
            if (empty(static::$files['file'])) {
                Incident::new()
                    ->setType('Invalid file upload detected')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(tr('Received invalid $_FILES data from client'))
                    ->setDetails([
                        '$_FILES'   => static::$files,
                        '$_GET'     => GetValidator::new()->getSource(),
                        '$_POST'    => PostValidator::new()->getSource(),
                        '$_SESSION' => $_SESSION,
                        '$_SERVER'  => $_SERVER
                    ])
                    ->save()
                    ->throw(ValidationFailedException::class);

            } else {
                if (is_array(static::$files['file']['name'])) {
                    static::restructureMultipleFiles();

                } else {
                    static::restructureSingleFile();
                }

                Log::notice(tr('Found ":count" uploaded files', [':count' => count (static::$files)]), 5);
            }
        }
    }


    /**
     * Restructures a single file in the internal $_FILES array
     *
     * @return void
     */
    protected static function restructureSingleFile(): void
    {
        static::$files = [0 => static::$files['file']];
    }


    /**
     * Restructures multiple files in the internal $_FILES array
     *
     * @return void
     */
    protected static function restructureMultipleFiles(): void
    {
        $files = [];

        foreach (static::$files['file'] as $key => $data) {
            foreach ($data as $id => $value) {
                if (empty($files[$id])) {
                    $files[$id] = [];
                }

                $files[$id][$key] = $value;
            }
        }

        static::$files = $files;
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
            if (static::$files) {
                Log::action(tr('Processing ":count" uploaded files', [
                    ':count' => count(static::$files)
                ]));

                foreach (static::$files as &$file) {
                    try {
                        $file = $this->processFile($file);

                    } catch (FileUploadException $e) {
                        if ($this->exception) {
                            throw $e;
                        }

                        // Register the incident in text & developer incidents log
                        Incident::new()
                                ->setSeverity(EnumSeverity::low)
                                ->setTitle($e->getMessage())
                                ->setDetails([
                                    'file'      => $file,
                                    'exception' => $e
                                ])
                                ->save();
                    }
                }

                unset($file);
            }
        }

        // Set the is_processed flag
        $this->__process();
        return $this;
    }


    /**
     * Processes the specified file
     *
     * @param array $file
     *
     * @return FsUploadedFileInterface
     */
    protected function processFile(array $file): FsUploadedFileInterface
    {
        Log::action(tr('Processing uploaded file ":file"', [
            ':file' => $file['name']
        ]), 4);

        // Register the uploaded file, then find a handler for this file's mime type
        $file     = Upload::newFromSource($file)->save();
        $file     = new FsUploadedFile($file);
        $handler  = $this->getHandler($file);

        // Execute the handler for this file
        return $handler->process($file);
    }


    /**
     * Returns the handler for the specified file mimetype if available, throws an FileUploadHandlerException if not
     *
     * @param FsUploadedFileInterface $file
     *
     * @return UploadHandlerInterface
     */
    protected function getHandler(FsUploadedFileInterface $file): UploadHandlerInterface
    {
        $file_mimetype = $file->getMimetype();

        foreach ($this->source as $mimetype => $handler) {
            if (str_starts_with($file_mimetype, $mimetype)) {
                break;
            }

            unset($handler);
        }

        if (empty($handler)) {
            throw new FileUploadHandlerException(tr('Cannot process uploaded file ":file" with size ":size" and mimetype ":mimetype", it has no handler specified', [
                ':file'     => $file->getRealName(),
                ':size'     => $file->getSize(),
                ':mimetype' => $file->getMimetype(),
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
     * @return IteratorInterface
     */
    public function getProcessedFiles(): IteratorInterface
    {
        return new Iterator(static::$processed);
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
        return (bool) count(static::$files);
    }


    /**
     * Returns if any files were processed
     *
     * @return bool
     */
    public function hasProcessedFiles(): bool
    {
        return (bool) count(static::$processed);
    }


    /**
     * Returns if any files were rejected
     *
     * @return bool
     */
    public function hasRejectedFiles(): bool
    {
        return (bool) count(static::$rejected);
    }
}
