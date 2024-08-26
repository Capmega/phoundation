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

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataMimetype;
use Phoundation\Data\Traits\TraitDataRequestMethod;
use Phoundation\Data\Traits\TraitDataTimeout;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpConfigurationException;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\Interfaces\DropzoneInterface;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;


class Dropzone implements DropzoneInterface
{
    use TraitStaticMethodNew;
    use TraitDataUrl;
    use TraitDataMimetype;
    use TraitDataRequestMethod {
        setRequestMethod as protected __setRequestMethod;
    }
    use TraitDataTimeout;


    /**
     * The selector to use for the drag/drop component
     *
     * @var string|null
     */
    protected string|null $selector;

    /**
     * The minimum number of files that may be processed by this upload handler
     *
     * @var int|null $min_files
     */
    protected ?int $min_files = 1;

    /**
     * The maximum number of files that may be processed by this upload handler
     *
     * @var int|null $max_files
     */
    protected ?int $max_files = 1;

    /**
     * Tracks the maximum filesize (in bytes) that is allowed to be uploaded
     *
     * @var int|null $max_file_size
     */
    protected ?int $max_file_size = null;

    /**
     * Tracks how many file uploads to process in parallel
     *
     * @var int|null $parallel_uploads
     */
    protected ?int $parallel_uploads = null;

    /**
     * Tracks if the file in this handler has been validated or not
     *
     * @var bool $validated
     */
    protected bool $validated = false;

    /**
     * Tracks additional headers tp be sent
     *
     * @var IteratorInterface|null $headers
     */
    protected ?IteratorInterface $headers = null;

    /**
     * Tracks whether hidden files in directories should be ignored
     *
     * @var bool $ignore_hidden_files
     */
    protected bool $ignore_hidden_files = true;

    /**
     * Tracks the file's mime type or extension against this list. This is a comma separated list of mime types or file
     * extensions.
     *
     * @var ?IteratorInterface $accepted_files
     */
    protected ?IteratorInterface $accepted_files = null;

    /**
     * Tracks whether you want files to be uploaded in chunks to your server
     *
     * @var bool|null $chunking
     */
    protected ?bool $chunking = null;

    /**
     * Tracks whether *every* file should be chunked, even if the file size is below chunkSize
     *
     * @var bool|null $force_chunking
     */
    protected ?bool $force_chunking = null;

    /**
     * Tracks the chunk size in bytes
     *
     * @var int|null $chunk_size
     */
    protected ?int $chunk_size = null;

    /**
     * Tracks whether individual chunks of a file are being uploaded simultaneously
     *
     * @var bool|null $parallel_chunk_uploads
     */
    protected ?bool $parallel_chunk_uploads = null;

    /**
     * Tracks whether a chunk should be retried if it fails.
     *
     * @var int|null $retry_chunks
     */
    protected ?int $retry_chunks = null;

    /**
     * Tracks how many times should uploads be retried.
     *
     * @var int|null $retry_chunks_limit
     */
    protected ?int $retry_chunks_limit = null;

    /**
     * Tracks whether to send multiple files in one request
     *
     * @var bool|null $upload_multiple
     */
    protected ?bool $upload_multiple = true;

    /**
     * The upload handler to which this dropzone belongs
     *
     * @var UploadHandlerInterface $handler
     */
    protected UploadHandlerInterface $handler;


    /**
     * UploadHandler class constructor
     *
     * @param UploadHandlerInterface $handler
     * @param string|null            $selector
     */
    public function __construct(UploadHandlerInterface $handler, ?string $selector = null)
    {
        $this->setUrl(Url::getWww())
             ->setSelector($selector)
             ->handler = $handler;
    }


    /**
     * Returns a new UploadHandler class
     *
     * @param UploadHandlerInterface $handler
     * @param string|null            $selector
     *
     * @return static
     */
    public static function new(UploadHandlerInterface $handler, ?string $selector = null): static
    {
        return new static($handler, $selector);
    }


    /**
     * Returns the handler to which this dropzone belongs
     *
     * @return UploadHandlerInterface
     */
    public function getHandler(): UploadHandlerInterface
    {
        return $this->handler;
    }


    /**
     * Returns the maximum filesize (in bytes) that is allowed to be uploaded
     *
     * @return int|null
     */
    public function getMaxFileSize(): ?int
    {
        return $this->max_file_size;
    }


    /**
     * Sets the maximum filesize (in bytes) that is allowed to be uploaded
     *
     * @param string|int|null $max_file_size
     *
     * @return static
     */
    public function setMaxFileSize(string|int|null $max_file_size): static
    {
        if ($max_file_size) {
            if (!is_int($max_file_size)) {
                $max_file_size = Numbers::fromBytes($max_file_size);
            }

            if ($max_file_size < 1) {
                throw new OutOfBoundsException(tr('The $max_files parameter ":value" is invalid, it cannot be lower than 1', [
                    ':value' => $max_file_size,
                ]));
            }

            $ini_size = get_null(ini_get('max_file_size'));

            if ($ini_size and ($max_file_size > $ini_size)) {
                throw new PhpConfigurationException(tr('The total maximum file size allowed for this upload handler is ":size", but the server configuration allows a maximum post size of ":post_max"', [
                    ':size'     => Numbers::fromBytes($max_file_size),
                    ':post_max' => Numbers::fromBytes(($ini_size ?? -1)),
                ]));
            }

            $this->max_file_size = $max_file_size;

            $this->checkPhpSettings();

        } else {
            $this->max_file_size = get_null($max_file_size);
        }


        return $this;
    }


    /**
     * Returns whether to send multiple files in one request
     *
     * @return bool|null
     */
    public function getUploadMultiple(): ?bool
    {
        return $this->upload_multiple;
    }


    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param bool|null $upload_multiple
     *
     * @return static
     */
    public function setUploadMultiple(?bool $upload_multiple): static
    {
        $this->upload_multiple = $upload_multiple;

        if ($this->upload_multiple === false) {
            $this->max_files = 1;
        }

        return $this;
    }


    /**
     * Returns the minimum number of files that will be allowed to be uploaded
     *
     * @return int|null
     */
    public function getMinFiles(): ?int
    {
        return $this->min_files;
    }


    /**
     * Sets the minimum number of files that will be allowed to be uploaded
     *
     * @param int|null $min_files
     *
     * @return static
     */
    public function setMinFiles(?int $min_files): static
    {
        if ($min_files) {
            if ($min_files < 1) {
                throw new OutOfBoundsException(tr('The min_files parameter value ":value" is invalid, it cannot be lower than 1', [
                    ':value' => $min_files,
                ]));
            }

            if ($min_files > $this->max_files) {
                throw new OutOfBoundsException(tr('The min_files parameter value ":value" is invalid, it cannot be higher than the max_files value of ":max"', [
                    ':value' => $min_files,
                    ':max'   => $this->max_files,
                ]));
            }
        }

        $this->min_files = get_null($min_files);

        $this->checkPhpSettings();

        return $this;
    }


    /**
     * Returns the maximum number of files that will be allowed to be uploaded
     *
     * @return int|null
     */
    public function getMaxFiles(): ?int
    {
        return $this->max_files;
    }


    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param int|null $max_files
     *
     * @return static
     */
    public function setMaxFiles(?int $max_files): static
    {
        if ($max_files) {
            if ($max_files < 1) {
                throw new OutOfBoundsException(tr('The max_files parameter value ":value" is invalid, it cannot be lower than 1', [
                    ':value' => $max_files,
                ]));
            }

            if ($max_files < $this->min_files) {
                throw new OutOfBoundsException(tr('The max_files parameter value ":value" is invalid, it cannot be lower than the min_files value of ":max"', [
                    ':value' => $max_files,
                    ':max'   => $this->min_files,
                ]));
            }

            if ($max_files < $this->parallel_uploads) {
                throw new OutOfBoundsException(tr('The max_files parameter cannot be lower than the parallel_uploads value ":value"', [
                    ':value' => $this->max_files,
                ]));
            }

            if ($max_files > ini_get('max_file_uploads')) {
                throw new PhpConfigurationException(tr('The max_files value ":value" is higher than the maximum allowed by PHP configuration of ":php"', [
                    ':value' => $this->max_files,
                    'php'    => ini_get('max_file_uploads'),
                ]));
            }
        }

        $this->max_files = get_null($max_files);

        $this->checkPhpSettings();

        return $this;
    }


    /**
     * Returns how many file uploads to process in parallel
     *
     * @return int|null
     */
    public function getParallelUploads(): ?int
    {
        return $this->parallel_uploads;
    }


    /**
     * Sets how many file uploads to process in parallel
     *
     * @param int|null $parallel_uploads
     *
     * @return static
     */
    public function setParallelUploads(?int $parallel_uploads): static
    {
        if ($parallel_uploads) {
            if ($parallel_uploads < 1) {
                throw new OutOfBoundsException(tr('The max_files parameter value ":value" is invalid, it cannot be lower than 1', [
                    ':value' => $parallel_uploads,
                ]));
            }

            if ($this->max_files and ($parallel_uploads > $this->max_files)) {
                throw new OutOfBoundsException(tr('The parallel_uploads parameter cannot be higher than the max_files value ":value"', [
                    ':value' => $this->max_files,
                ]));
            }
        }

        $this->parallel_uploads = $parallel_uploads;

        return $this;
    }


    /**
     * Returns the HTML selector to which the drag/drop will be attached
     *
     * @return string|null
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }


    /**
     * Sets the HTML selector to which the drag/drop will be attached
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setSelector(?string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }


    /**
     * Returns Iterator object containing additional headers tp be sent
     *
     * @return IteratorInterface
     */
    public function getHeaders(): IteratorInterface
    {
        if (empty($this->headers)) {
            $this->headers = Iterator::new()->setAcceptedDataTypes('string');
        }

        return $this->headers;
    }


    /**
     * Returns whether hidden files in directories should be ignored
     *
     * @return bool
     */
    public function getIgnoreHiddenFiles(): bool
    {
        return $this->ignore_hidden_files;
    }


    /**
     * Sets whether hidden files in directories should be ignored
     *
     * @param bool $ignore_hidden_files
     *
     * @return static
     */
    public function setIgnoreHiddenFiles(bool $ignore_hidden_files): static
    {
        $this->ignore_hidden_files = $ignore_hidden_files;

        return $this;
    }


    /**
     * Returns Iterator object containing additional headers tp be sent
     *
     * @return IteratorInterface
     */
    public function getAcceptedFiles(): IteratorInterface
    {
        if (empty($this->accepted_files)) {
            $this->accepted_files = Iterator::new()->setAcceptedDataTypes('string');
        }

        return $this->accepted_files;
    }


    /**
     * Returns whether you want files to be uploaded in chunks to your server
     *
     * @return bool|null
     */
    public function getChunking(): ?bool
    {
        return $this->chunking;
    }


    /**
     * Sets whether you want files to be uploaded in chunks to your server
     *
     * @param bool|null $chunking
     *
     * @return static
     */
    public function setChunking(?bool $chunking): static
    {
        $this->chunking = $chunking;

        return $this;
    }


    /**
     * Returns whether *every* file should be chunked, even if the file size is below chunkSize
     *
     * @return bool|null
     */
    public function getForceChunking(): ?bool
    {
        return $this->force_chunking;
    }


    /**
     * Sets whether *every* file should be chunked, even if the file size is below chunkSize
     *
     * @param bool|null $force_chunking
     *
     * @return static
     */
    public function setForceChunking(?bool $force_chunking): static
    {
        $this->force_chunking = $force_chunking;

        return $this;
    }


    /**
     * Returns the chunk size in bytes
     *
     * @return int|null
     */
    public function getChunkSize(): ?int
    {
        return $this->chunk_size;
    }


    /**
     * Sets the chunk size in bytes
     *
     * @param int|null $chunk_size
     *
     * @return static
     */
    public function setChunkSize(?int $chunk_size): static
    {
        $this->chunk_size = $chunk_size;

        return $this;
    }


    /**
     * Returns the chunk size in bytes
     *
     * @return bool|null
     */
    public function getParallelChunkUploads(): ?bool
    {
        return $this->parallel_chunk_uploads;
    }


    /**
     * Sets the chunk size in bytes
     *
     * @param bool|null $parallel_chunk_uploads
     *
     * @return static
     */
    public function setParallelChunkUploads(?bool $parallel_chunk_uploads): static
    {
        $this->parallel_chunk_uploads = $parallel_chunk_uploads;

        return $this;
    }


    /**
     * Returns the chunk size in bytes
     *
     * @return int|null
     */
    public function getRetryChunks(): ?int
    {
        return $this->retry_chunks;
    }


    /**
     * Sets if a chunk should be retried if it fails
     *
     * @param int|null $retry_chunks
     *
     * @return static
     */
    public function setRetryChunks(?int $retry_chunks): static
    {
        $this->retry_chunks = $retry_chunks;

        return $this;
    }


    /**
     * Returns if a chunk should be retried if it fails
     *
     * @return int|null
     */
    public function getRetryChunksLimit(): ?int
    {
        return $this->retry_chunks_limit;
    }


    /**
     * Sets how many times should chunk uploads be retried
     *
     * @param int|null $retry_chunks_limit
     *
     * @return static
     */
    public function setRetryChunksLimit(?int $retry_chunks_limit): static
    {
        $this->retry_chunks_limit = $retry_chunks_limit;

        return $this;
    }


    /**
     * Renders the drag/drop code for this handler, if needed
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $options = $this->generateOptionsJson([
            'url'                   => $this->url,
            'method'                => $this->request_method->value,
            'maxFiles'              => $this->max_files,
            'parallelUploads'       => $this->parallel_uploads,
            'maxFileSize'           => $this->max_file_size,
            'timeout'               => $this->timeout,
            'headers'               => $this->headers,
            'ignoreHiddenFiles'     => $this->ignore_hidden_files,
            'acceptedFiles'         => $this->accepted_files,
            'chunking'              => $this->chunking,
            'forceChunking'         => $this->force_chunking,
            'chunkSize'             => $this->chunk_size,
            'parallelChunkUploads'  => $this->parallel_chunk_uploads,
            'retryChunks'           => $this->retry_chunks,
            'retryChunksLimit'      => $this->retry_chunks_limit,
            'uploadMultiple'        => $this->upload_multiple,
// TODO Implement other options below
            'createImageThumbnails' => false,
            'autoProcessQueue'      => true,
        ]);

        // Remove the trailing } and attach the following code
        $options  = substr($options, 0, -1);
        $options .= ',' . PHP_EOL . ' "init": function () {
                                                  this.on("success", function (file, result) {
                                                      $.filterPhoundation(result);
                                                  });
                                              }}';

// TODO Implement other options below
//            'withCredentials'       => $this->>with_credentials,
//            'paramName'             => $this->param_name,
//            'createImageThumbnails' => $this->create_image_thumbnails,
//            'maxThumbnailFilesize'  => $this->max_thumbnail_file_size,
//            'thumbnailWidth'        => $this->thumbnail_width,
//            'thumbnailHeight'       => $this->thumbnail_height,
//            'thumbnailMethod'       => $this->thumbnail_method,
//            'resizeWidth'           => $this->resize_width,
//            'resizeHeight'          => $this->resize_height,
//            'resizeMimeType'        => $this->resize_mimetype,
//            'resizeQuality'         => $this->resize_quality,
//            'resizeMethod'          => $this->resize_method,
//            'filesizeBase'          => $this->filesize_base,
//            'clickable'             => $this->clickable,
//            'autoProcessQueue'      => $this->auto_process_queue,
//            'autoQueue'             => $this->auto_queue,
//            'addRemoveLinks'        => $this->add_remove_links,
//            'previewsContainer'     => $this->previews_container,
//            'disablePreviews'       => $this->disable_previews,
//            'hiddenInputContainer'  => $this->hidden_input_container,
//            'capture'               => $this->capture,
//            'renameFilename'        => $this->rename_filename,
//            'renameFile'            => $this->rename_file,
//            'forceFallback'         => $this->force_fallback,

        Response::addScript('var myFileUploadDropZone = new Dropzone("' . $this->selector . '", ' . $options . ')');

        return null;
    }


    /**
     * Generates the options JSON string
     *
     * @param array $options
     *
     * @return string
     */
    protected function generateOptionsJson(array $options = []): string
    {
        $return = [];

        foreach ($options as $key => $value) {
            if ($value === null) {
                continue;
            }

            $return[$key] = $value;
        }

        return Json::encode($return);
    }


    /**
     * Ensure that the PHP settings allow us to apply restrictions as specified
     *
     * @return void
     */
    protected function checkPhpSettings(): void
    {
        $post_max = ini_get('post_max_size');
        $post_max = Numbers::fromBytes($post_max);
        $real_max = $this->max_files * $this->max_file_size;

        if ($real_max > $post_max) {
            throw new PhpConfigurationException(tr('The total maximum size of each files ":file_size" times the maximum amount of files ":max_files" allowed for this upload handler is ":total_size", which is more than the configured maximum PHP post size of ":post_max"', [
                ':file_size'  => $this->max_file_size,
                ':max_files'  => $this->max_files,
                ':total_size' => $real_max,
                ':post_max'   => $post_max,
            ]));
        }
    }


    /**
     * Sets the form request method
     *
     * @param EnumHttpRequestMethod $request_method
     *
     * @return static
     */
    public function setRequestMethod(EnumHttpRequestMethod $request_method): static
    {
        switch ($request_method) {
            case EnumHttpRequestMethod::post:
            case EnumHttpRequestMethod::put:
                // Only these two methods are supported by dropzone
                break;

            default:
                throw new OutOfBoundsException(tr('The request method "EnumHttpRequestMethod :method" is not supported for this upload handler, only "EnumHttpRequestMethod::post" and "EnumHttpRequestMethod::put" are allowed.', [
                    ':method' => $request_method,
                ]));
        }

        return $this->__setRequestMethod($request_method);
    }
}
