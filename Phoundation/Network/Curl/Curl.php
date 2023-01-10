<?php

namespace Phoundation\Network\Curl;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Network\Browsers\UserAgents;
use Phoundation\Web\Exception\WebException;



/**
 * Class Curl
 *
 * This class manages the basic Curl functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
abstract class Curl
{
    /**
     * The URL to which the Curl request will be made
     *
     * @var string
     */
    protected string $url;

    /**
     * The actual cURL interface
     *
     * @var mixed $curl
     */
    protected mixed $curl;

    /**
     * The HTTP method for the cURL request
     *
     * @var string $method
     */
    #[ExpectedValues(values: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'CONNECT', 'OPTIONS', 'TRACE'])]
    protected string $method;

    /**
     * If true, page redirects are followed. NOT recommended true for POST requests!
     *
     * @var bool $follow_location
     */
    protected bool $follow_location = true;

    /**
     * The user agent to be used for this request
     *
     * @var string|null
     */
    protected ?string $user_agent = null;

    /**
     * The result headers from the request. NULL if the request has not yet been executed
     *
     * @var array|null
     */
    protected ?array $result_headers = null;

    /**
     * The result data from the request. NULL if the request has not yet been executed
     *
     * @var string|null
     */
    protected ?string $result_data = null;

    /**
     * The path where the cURL requests will be logged
     *
     * @var string|null $log_path
     */
    protected ?string $log_path = PATH_DATA . 'log/curl/';

    /**
     * The maximum amount of retries executed for this request
     *
     * @var int $retries
     */
    protected int $retries = 10;

    /**
     * The amount of retries executed for this request
     *
     * @var int $retry
     */
    protected int $retry = 0;

    /**
     * Cache timeout
     *
     * @var int $cache
     */
    protected int $cache = 0;

    /**
     * The amount of time in seconds before a connection times out
     *
     * @var int $connect_timeout
     */
    protected int $connect_timeout = 10;

    /**
     * The amount of time this object will wait before retrying a failed connection
     *
     * @var int $sleep
     */
    protected int $sleep = 2;

    /**
     * If true, will store and use cookies
     *
     * @var bool $get_cookies
     */
    protected bool $get_cookies = true;

    /**
     * The file where cookies will be written to
     *
     * @var string|null $cookie_file
     */
    protected ?string $cookie_file = null;

    /**
     * The cookies that will be sent for this request
     *
     * @var array|null $cookies
     */
    protected ?array $cookies = null;

    /**
     * The cookies received from the remote server for this request
     *
     * @var array|null $result_cookies
     */
    protected ?array $result_cookies = null;

    /**
     * The status information about this request. NULL if it hasn't been executed yet
     *
     * @var array|null $result_status
     */
    protected ?array $result_status = null;

    /**
     * Sets if the connection will be closed after the request has been completed
     *
     * @var bool $close
     */
    protected bool $close = true;

    /**
     * The file to which the result will be saved
     *
     * @var string|null $save_to_file
     */
    protected ?string $save_to_file = null;

    /**
     * The referer header to send
     *
     * @var string|null $referer
     */
    protected ?string $referer = null;

    /**
     * The user password to send with the request
     *
     * @var string|null $user_password
     */
    protected ?string $user_password = null;

    /**
     * Indicates to use DNS cache or not
     *
     * @var bool $dns_cache
     */
    protected bool $dns_cache = true;

    /**
     * Indicates to verify the SSL certificate or to ignore invalid certificates
     *
     * @var bool $verify_ssl
     */
    protected bool $verify_ssl = true;

    /**
     * The timeout for the request
     *
     * @var int $timeout
     */
    protected int $timeout = 30;

    /**
     * Extra other cURL options for this request
     *
     * @var array $options
     */
    protected array $options = [];

    /**
     * Multipart or not
     *
     * @var bool $multipart
     */
    protected bool $multipart = false;



    /**
     * Curl class constructor
     */
    public function __construct(?string $url = null)
    {
        if (!extension_loaded('curl')) {
            throw new WebException(tr('The PHP "curl" module is not available, please install it first. On ubuntu install the module with "apt -y install php-curl"; a restart of the webserver or php fpm server may be required'));
        }

        $this->url  = $url;
    }



    /**
     * Returns a new cURL class
     *
     * @param string|null $url
     * @return static
     */
    public static function new(?string $url = null): static
    {
        return new static($url);
    }



    /**
     * Returns the request method
     *
     * @return string
     */
    #[ExpectedValues(values: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'CONNECT', 'OPTIONS', 'TRACE'])]
    public function getMethod(): string
    {
        return $this->method;
    }



    /**
     * Sets the request method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(#[ExpectedValues(values: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'CONNECT', 'OPTIONS', 'TRACE'])] string $method): static
    {
        $this->method = $method;

        if ($method === 'POST') {
            // Disable cache on POST requests, disable follow location too as it would convert POST into GET requests
            $this->cache           = 0;
            $this->follow_location = false;
        }

        return $this;
    }



    /**
     * Returns if the request will follow page redirects
     *
     * @return bool
     */
    public function getFollowLocation(): bool
    {
        return $this->follow_location;
    }



    /**
     * Sets if the request will follow page redirects
     *
     * @param bool $follow_location
     * @return static
     */
    public function setFollowLocation(bool $follow_location): static
    {
        if ($follow_location and ($this->method === 'POST')) {
            throw new OutOfBoundsException(tr('Cannot follow location for POST method requests'));
        }

        $this->follow_location = $follow_location;
        return $this;
    }



    /**
     * Returns the user agent to be used for this request
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        // Default useragent
        if ($this->user_agent === null) {
            return UserAgents::getRandom();
        }

        return $this->user_agent;
    }



    /**
     * Sets the user agent to be used for this request
     *
     * @param string|null $user_agent
     * @return static
     */
    public function setUserAgent(?string $user_agent): static
    {
        $this->user_agent = $user_agent;
        return $this;
    }



    /**
     * Returns the path to where cURL will log. "" if logging was disabled
     *
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->log_path;
    }



    /**
     * Sets the path to where cURL will log. NULL or "" if logging has to be disabled
     *
     * @param string $log_path
     * @return static
     */
    public function setLogPath(string $log_path): static
    {
        $this->log_path = $log_path;

        if ($this->log_path) {
            Path::new(dirname($this->log_path), PATH_DATA . 'log/')->ensure();
        }

        return $this;
    }



    /**
     * Returns the amount of retries executed for this request
     *
     * @return int
     */
    public function getRetry(): int
    {
        return $this->retry;
    }



    /**
     * Sets the maximum amount of retries executed for this request
     *
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }



    /**
     * Returns the maximum amount of retries executed for this request
     *
     * @param int $retries
     * @return static
     */
    public function setRetries(int $retries): static
    {
        $this->retries = $retries;
        return $this;
    }



    /**
     * Sets the amount of time this object will wait before retrying a failed connection
     *
     * @return int
     */
    public function getSleep(): int
    {
        return $this->sleep;
    }



    /**
     * Returns the amount of time this object will wait before retrying a failed connection
     *
     * @param int $sleep
     * @return static
     */
    public function setSleep(int $sleep): static
    {
        $this->sleep = $sleep;
        return $this;
    }



    /**
     * Sets the amount of time in seconds before a complete request times out
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }



    /**
     * Returns the amount of time in seconds before a complete request times out
     *
     * @param int $timeout
     * @return static
     */
    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }



    /**
     * Sets the amount of time in seconds before a connection times out
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connect_timeout;
    }



    /**
     * Returns the amount of time in seconds before a connection times out
     *
     * @param int $connect_timeout
     * @return static
     */
    public function setConnectTimeout(int $connect_timeout): static
    {
        $this->connect_timeout = $connect_timeout;
        return $this;
    }



    /**
     * Returns the cURL interface
     *
     * @return mixed
     */
    public function getCurl(): mixed
    {
        return $this->curl;
    }



    /**
     * Returns if the request should use multipart or not
     *
     * @return bool
     */
    public function getMultipart(): bool
    {
        return $this->multipart;
    }



    /**
     * Sets if the request should use multipart or not
     *
     * @param bool $multipart
     * @return static
     */
    public function setMultipart(bool $multipart): static
    {
        $this->multipart = $multipart;
        return $this;
    }



    /**
     * Returns if the connection will be closed once the request has been completed
     *
     * @return bool
     */
    public function getClose(): bool
    {
        return $this->close;
    }



    /**
     * Returns if the connection will be closed once the request has been completed
     *
     * @param bool $close
     * @return static
     */
    public function setClose(bool $close): static
    {
        $this->close = $close;
        return $this;
    }



    /**
     * Returns if the DNS should use cache or not
     *
     * @return bool
     */
    public function getDnsCache(): bool
    {
        return $this->dns_cache;
    }



    /**
     * Sets if the DNS should use cache or not
     *
     * @param bool $dns_cache
     * @return static
     */
    public function setDnsCache(bool $dns_cache): static
    {
        $this->dns_cache = $dns_cache;
        return $this;
    }



    /**
     * Returns if the request will verify the SSL certificate or not
     *
     * @return bool
     */
    public function getVerifySsl(): bool
    {
        return $this->verify_ssl;
    }



    /**
     * Sets if the request will verify the SSL certificate or not
     *
     * @param bool $verify_ssl
     * @return static
     */
    public function setVerifySsl(bool $verify_ssl): static
    {
        $this->verify_ssl = $verify_ssl;
        return $this;
    }



    /**
     * Sets if object will store and use cookies
     *
     * @return bool
     */
    public function getGetCookies(): bool
    {
        return $this->get_cookies;
    }



    /**
     * Returns if object will store and use cookies
     *
     * @param bool $get_cookies
     * @return static
     */
    public function setGetCookies(bool $get_cookies): static
    {
        $this->get_cookies = $get_cookies;
        return $this;
    }



    /**
     * Sets what file cookies should be written to
     *
     * @return string
     */
    public function getCookieFile(): string
    {
        return $this->cookie_file;
    }



    /**
     * Returns if object will store and use cookies
     *
     * @param string $cookie_file
     * @return static
     */
    public function setCookieFile(string $cookie_file): static
    {
        $this->cookie_file = $cookie_file;
        return $this;
    }



    /**
     * Returns the cookies that were sent by the remote server for this request
     *
     * @return array
     */
    public function getResultCookies(): array
    {
        return $this->result_cookies;
    }



    /**
     * Returns the cookies that will be sent for this request
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }



    /**
     * Clears the cookies that will be sent for this request
     *
     * @return static
     */
    public function clearCookies(): static
    {
        $this->cookies = [];
        return $this;
    }



    /**
     * Sets the cookies that will be sent for this request
     *
     * @param array $cookies
     * @return static
     */
    public function setCookies(array $cookies): static
    {
        $this->cookies = [];
        return $this->addCookies($cookies);
    }



    /**
     * Adds the specified cookies that will be sent for this request
     *
     * @param array $cookies
     * @return static
     */
    public function addCookies(array $cookies): static
    {
        foreach ($cookies as $cookie) {
            $this->addCookie($cookie);
        }

        return $this;
    }



    /**
     * Adds the specified cookie that will be sent for this request
     *
     * @param string $cookie
     * @return static
     */
    public function addCookie(string $cookie): static
    {
        $this->cookies[] = $cookie;
        return $this;
    }



    /**
     * Sets if object will use local cache for this request
     *
     * @return bool
     */
    public function getCache(): bool
    {
        return $this->cache;
    }



    /**
     * Returns if object will use local cache for this request
     *
     * @param bool $cache
     * @return static
     */
    public function setCache(bool $cache): static
    {
        $this->cache = $cache;
        return $this;
    }



    /**
     * Returns the user_password header
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $this->user_password;
    }



    /**
     * Sets the user_password header
     *
     * @param string $user_password
     * @return static
     */
    public function setReferer(string $user_password): static
    {
        $this->user_password = $user_password;
        return $this;
    }



    /**
     * Returns the file to which the result will be saved
     *
     * @return string
     */
    public function getSaveToFile(): string
    {
        return $this->save_to_file;
    }



    /**
     * Sets the file to which the result will be saved
     *
     * @param string $save_to_file
     * @return static
     */
    public function setSaveToFile(string $save_to_file): static
    {
        $this->save_to_file = $save_to_file;
        return $this;
    }



    /**
     * Returns other extra cURL options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }



    /**
     * Clears other extra cURL options
     *
     * @return static
     */
    public function clearsOptions(): static
    {
        $this->options = [];
        return $this;
    }



    /**
     * Sets other extra cURL options
     *
     * @param array $options
     * @return static
     */
    public function setOptions(array $options): static
    {
        $this->options = [];
        return $this->addOptions($options);
    }



    /**
     * Adds other extra cURL options
     *
     * @param array $options
     * @return static
     */
    public function addOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $this->addOption($key, $value);
        }

        return $this;
    }


    /**
     * Adds another extra cURL option
     *
     * @param int $key
     * @param mixed $value
     * @return static
     */
    public function addOption(int $key, mixed $value): static
    {
        $this->options[$key] = $value;
        return $this;
    }



    /**
     * Returns the result headers
     *
     * @return array|null
     */
    public function getResultHeaders(): ?array
    {
        return $this->result_headers;
    }



    /**
     * Returns the result status
     *
     * @return array|null
     */
    public function getResultStatus(): ?array
    {
        return $this->result_status;
    }



    /**
     * Returns the result data
     *
     * @return array|null
     */
    public function getResultData(): ?string
    {
        return $this->result_data;
    }



    /**
     * Executes the request
     *
     * @return void
     */
    abstract public function execute(): void;



    /**
     * Prepare the request
     *
     * @return void
     */
    abstract protected function prepare(): void;
}