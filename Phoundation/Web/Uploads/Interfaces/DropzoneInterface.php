<?php

namespace Phoundation\Web\Uploads\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;


interface DropzoneInterface
{
    /**
     * Returns the maximum filesize (in bytes) that is allowed to be uploaded
     *
     * @return int|null
     */
    public function getMaxFileSize(): ?int;


    /**
     * Sets the maximum filesize (in bytes) that is allowed to be uploaded
     *
     * @param string|int|null $max_file_size
     *
     * @return static
     */
    public function setMaxFileSize(string|int|null $max_file_size): static;


    /**
     * Returns the maximum number of files that will be allowed to be uploaded
     *
     * @return int|null
     */
    public function getMaxFiles(): ?int;


    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param int|null $max_files
     *
     * @return static
     */
    public function setMaxFiles(?int $max_files): static;


    /**
     * Returns how many file uploads to process in parallel
     *
     * @return int|null
     */
    public function getParallelUploads(): ?int;


    /**
     * Sets how many file uploads to process in parallel
     *
     * @param int $parallel_uploads
     *
     * @return static
     */
    public function setParallelUploads(int $parallel_uploads): static;


    /**
     * Returns the HTML selector to which the drag/drop will be attached
     *
     * @return string|null
     */
    public function getSelector(): ?string;


    /**
     * Sets the HTML selector to which the drag/drop will be attached
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setSelector(?string $selector): static;


    /**
     * Returns Iterator object containing additional headers tp be sent
     *
     * @return IteratorInterface
     */
    public function getHeaders(): IteratorInterface;


    /**
     * Returns whether hidden files in directories should be ignored
     *
     * @return bool
     */
    public function getIgnoreHiddenFiles(): bool;


    /**
     * Sets whether hidden files in directories should be ignored
     *
     * @param bool $ignore_hidden_files
     *
     * @return static
     */
    public function setIgnoreHiddenFiles(bool $ignore_hidden_files): static;


    /**
     * Returns Iterator object containing additional headers tp be sent
     *
     * @return IteratorInterface
     */
    public function getAcceptedFiles(): IteratorInterface;


    /**
     * Returns whether you want files to be uploaded in chunks to your server
     *
     * @return bool|null
     */
    public function getChunking(): ?bool;


    /**
     * Sets whether you want files to be uploaded in chunks to your server
     *
     * @param bool|null $chunking
     *
     * @return static
     */
    public function setChunking(?bool $chunking): static;


    /**
     * Returns whether *every* file should be chunked, even if the file size is below chunkSize
     *
     * @return bool|null
     */
    public function getForceChunking(): ?bool;


    /**
     * Sets whether *every* file should be chunked, even if the file size is below chunkSize
     *
     * @param bool|null $force_chunking
     *
     * @return static
     */
    public function setForceChunking(?bool $force_chunking): static;


    /**
     * Returns the chunk size in bytes
     *
     * @return int|null
     */
    public function getChunkSize(): ?int;


    /**
     * Sets the chunk size in bytes
     *
     * @param int|null $chunk_size
     *
     * @return static
     */
    public function setChunkSize(?int $chunk_size): static;


    /**
     * Returns the chunk size in bytes
     *
     * @return bool|null
     */
    public function getParallelChunkUploads(): ?bool;


    /**
     * Sets the chunk size in bytes
     *
     * @param bool|null $parallel_chunk_uploads
     *
     * @return static
     */
    public function setParallelChunkUploads(?bool $parallel_chunk_uploads): static;


    /**
     * Returns the chunk size in bytes
     *
     * @return int|null
     */
    public function getRetryChunks(): ?int;


    /**
     * Sets if a chunk should be retried if it fails
     *
     * @param int|null $retry_chunks
     *
     * @return static
     */
    public function setRetryChunks(?int $retry_chunks): static;


    /**
     * Returns if a chunk should be retried if it fails
     *
     * @return int|null
     */
    public function getRetryChunksLimit(): ?int;


    /**
     * Sets how many times should chunk uploads be retried
     *
     * @param int|null $retry_chunks_limit
     *
     * @return static
     */
    public function setRetryChunksLimit(?int $retry_chunks_limit): static;


    /**
     * Renders the drag/drop code for this handler, if needed
     *
     * @return string|null
     */
    public function render(): ?string;


    /**
     * Sets the form request method
     *
     * @param EnumHttpRequestMethod $request_method
     *
     * @return static
     */
    public function setRequestMethod(EnumHttpRequestMethod $request_method): static;

    /**
     * Returns whether to send multiple files in one request
     *
     * @return bool|null
     */
    public function getUploadMultiple(): ?bool;

    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param bool|null $upload_multiple
     *
     * @return static
     */
    public function setUploadMultiple(?bool $upload_multiple): static;

    /**
     * Returns the mimetype
     *
     * @return string|null
     */
    public function getMimetype(): ?string;

    /**
     * Sets the mimetype
     *
     * @param string|null $mimetype
     *
     * @return static
     */
    public function setMimetype(string|null $mimetype): static;
}
