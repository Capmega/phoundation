<?php

namespace Phoundation\Network\Curl;

use CURLFile;
use Exception;
use Phoundation\Core\Log;
use Phoundation\Network\Exception\NetworkException;



/**
 * Class Curl
 *
 * This class manages Curl POST request functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Post extends Get
{
    /**
     * The content type header
     *
     * @var string|null $content_type
     */
    protected ?string $content_type = null;

    /**
     * The data to send with the POST request
     *
     * @var array|null $post_data
     */
    protected ?array $post_data = null;

    /**
     * The files to be uploaded, must be
     *
     * @var array|null $upload_files
     */
    protected ?array $upload_files = null;

    /**
     * If true, all post data will be URL encoded
     *
     * @var bool $post_url_encoded
     */
    protected bool $post_url_encoded = false;



    /**
     * Post class constructor
     *
     * @param string|null $url
     */
    public function __construct(?string $url = null)
    {
        $this->setMethod('POST');
        parent::__construct($url);
    }



    /**
     * Returns the content type header
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->content_type;
    }



    /**
     * Sets the content type header
     *
     * @param string|null $content_type
     * @return static
     */
    public function setContentType(?string $content_type): static
    {
        $this->content_type = $content_type;
        return $this;
    }



    /**
     * Returns if the POST data will be URL encoded or not
     *
     * @return string|null
     */
    public function getPostUrlEncoded(): ?string
    {
        return $this->post_url_encoded;
    }



    /**
     * Sets if the POST data will be URL encoded or not
     *
     * @param string|null $post_url_encoded
     * @return static
     */
    public function setPostUrlEncoded(?string $post_url_encoded): static
    {
        $this->post_url_encoded = $post_url_encoded;
        return $this;
    }



    /**
     * Returns all POST values
     *
     * @return array
     */
    public function getPostValues(): array
    {
        return $this->post_data;
    }



    /**
     * Sets POST values
     *
     * @param array $values
     * @return static
     */
    public function setPostValues(array $values): static
    {
        $this->post_data = [];
        return $this->addPostValues($values);
    }



    /**
     * Adds POST values
     *
     * @param array $values
     * @return static
     */
    public function addPostValues(array $values): static
    {
        foreach ($values as $key => $value) {
            $this->addPostValue($key, $value);
        }

        return $this;
    }


    /**
     * Adds another POST data key value
     *
     * @param int $key
     * @param mixed $value
     * @return static
     */
    public function addPostValue(int $key, mixed $value): static
    {
        $this->post_data[$key] = $value;
        return $this;
    }



    /**
     * Returns all files that will be uploaded over POST
     *
     * @return array
     */
    public function getPostFileUploads(): array
    {
        return $this->upload_files;
    }



    /**
     * Sets all files that will be uploaded over POST
     *
     * @param array $files
     * @return static
     */
    public function setPostFileUploads(array $files): static
    {
        $this->upload_files = [];
        return $this->addPostValues($files);
    }



    /**
     * Adds POST values
     *
     * @param array $files
     * @return static
     */
    public function addPostFileUploads(array $files): static
    {
        foreach ($files as $file) {
            $this->addPostFileUpload($file);
        }

        return $this;
    }



    /**
     * Adds another POST data key value
     *
     * @param CURLFile $file
     * @return static
     */
    public function addPostFileUpload(CURLFile $file): static
    {
        $this->multipart = true;
        $this->post_data[] = $file;
        return $this;
    }



    /**
     * Executes the POST request
     *
     * @return void
     */
    public function execute(): void
    {
        // Disable 301 302 location header following since this would cause the POST to go to GET
        $this->follow_location = false;

        parent::execute();
    }



    /**
     * Prepares the POST request
     *
     * @return void
     */
    protected function prepare(): void
    {
        parent::prepare();

        curl_setopt($this->curl, CURLOPT_POST, true);

        // Log cURL request?
        if ($this->log_path) {
            Log::action(tr('Sending the following data'));
            Log::printr($this->post_data);
        }

        if ($this->content_type) {
            curl_setopt($this->curl, CURLINFO_CONTENT_TYPE, $this->content_type);
        }

        if ($this->post_url_encoded) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->post_data));
        } else {
            try {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->post_data);

            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'Array to string conversion')) {
                    throw new NetworkException(tr('CURLOPT_POSTFIELDS failed with "Array to string conversion", this is very likely because the specified post array is a multi dimensional array, while CURLOPT_POSTFIELDS only accept one dimensional arrays. Please set $params[posturlencoded] = true to use http_build_query() to set CURLOPT_POSTFIELDS instead'), 'invalid');
                }
            }
        }
    }
}