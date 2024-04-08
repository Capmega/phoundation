<?php

declare(strict_types=1);

namespace Phoundation\Network\Curl;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Network\Browsers\UserAgents;
use Phoundation\Network\Curl\Interfaces\CurlInterface;
use Phoundation\Web\Exception\WebException;
use Stringable;

/**
 * Class Curl
 *
 * This class manages the basic Curl functionality
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */
abstract class Curl implements CurlInterface
{
    use TraitDataUrl;

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
    #[ExpectedValues(values: [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'CONNECT',
        'OPTIONS',
        'TRACE',
    ])]
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
     * The request headers for the request. NULL if the request has not yet been executed
     *
     * @var array $request_headers
     */
    protected array $request_headers = [];

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
     * @var string|null $log_directory
     */
    protected ?string $log_directory = null;

    /**
     * File access restrictions for logging
     *
     * @var Restrictions|null $log_restrictions
     */
    protected ?Restrictions $log_restrictions = null;

    /**
     * The maximum number of retries executed for this request
     *
     * @var int $retries
     */
    protected int $retries = 5;

    /**
     * The number of retries executed for this request
     *
     * @var int $retry
     */
    protected int $retry = 0;

    /**
     * Cache timeout
     *
     * @var int $cache_timeout
     */
    protected int $cache_timeout = 0;

    /**
     * The number of time in seconds before a connection times out
     *
     * @var int $connect_timeout
     */
    protected int $connect_timeout = 2;

    /**
     * The number of time this object will wait before retrying a failed connection
     *
     * @var int $sleep
     */
    protected int $sleep = 1;

    /**
     * If true, will store and use cookies
     *
     * @var bool $get_cookies
     */
    protected bool $get_cookies = true;

    /**
     * If true, cURL will return more meta-information
     *
     * @var bool $verbose
     */
    protected bool $verbose = false;

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
     * Possible options:
     *
     * CURL_HTTP_VERSION_NONE (default, lets CURL decide which version to use)
     * CURL_HTTP_VERSION_1_0 (forces HTTP/1.0),
     * CURL_HTTP_VERSION_1_1 (forces HTTP/1.1),
     * CURL_HTTP_VERSION_2_0 (attempts HTTP 2),
     * CURL_HTTP_VERSION_2 (alias of CURL_HTTP_VERSION_2_0),
     * CURL_HTTP_VERSION_2TLS (attempts HTTP 2 over TLS (HTTPS) only)
     * CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE
     *
     * @var int $http_version
     */
    #[ExpectedValues(values: [
        CURL_HTTP_VERSION_NONE,
        CURL_HTTP_VERSION_1_0,
        CURL_HTTP_VERSION_1_1,
        CURL_HTTP_VERSION_2_0,
        CURL_HTTP_VERSION_2,
        CURL_HTTP_VERSION_2TLS,
        CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE,
    ])]
    protected int $http_version = CURL_HTTP_VERSION_NONE;


    /**
     * Curl class constructor
     */
    public function __construct(Stringable|string|null $url = null)
    {
        if (!extension_loaded('curl')) {
            throw new WebException(tr('The PHP "curl" module is not available, please install it first. On ubuntu install the module with "apt -y install php-curl"; a restart of the webserver or php fpm server may be required'));
        }
        // Verbose is always on when running in debug mode
        if (Debug::getEnabled()) {
            $this->verbose = true;
        }
        $this->url   = (string) $url;
        $this->retry = 0;
        $this->setLogDirectory(DIRECTORY_DATA . 'log/curl/');
        // Setup new cURL request
        $this->curl = curl_init();
    }


    /**
     * Returns a new cURL class
     *
     * @param Stringable|string|null $url
     *
     * @return static
     */
    public static function new(Stringable|string|null $url = null): static
    {
        return new static($url);
    }


    /**
     * Returns the request method
     *
     * @return int
     */
    #[ExpectedValues(values: [
        CURL_HTTP_VERSION_NONE,
        CURL_HTTP_VERSION_1_0,
        CURL_HTTP_VERSION_1_1,
        CURL_HTTP_VERSION_2_0,
        CURL_HTTP_VERSION_2,
        CURL_HTTP_VERSION_2TLS,
        CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE,
    ])]
    public function getHttpVersion(): int
    {
        return $this->http_version;
    }


    /**
     * Sets the request method
     *
     * @param int $http_version
     *
     * @return static
     */
    public function setHttpVersion(#[ExpectedValues(values: [
        CURL_HTTP_VERSION_NONE,
        CURL_HTTP_VERSION_1_0,
        CURL_HTTP_VERSION_1_1,
        CURL_HTTP_VERSION_2_0,
        CURL_HTTP_VERSION_2,
        CURL_HTTP_VERSION_2TLS,
        CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE,
    ])] int $http_version): static
    {
        $this->http_version = $http_version;

        return $this;
    }


    /**
     * Returns the request method
     *
     * @return string
     */
    #[ExpectedValues(values: [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'CONNECT',
        'OPTIONS',
        'TRACE',
    ])]
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * Sets the request method
     *
     * @param string $method
     *
     * @return static
     */
    public function setMethod(#[ExpectedValues(values: [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'CONNECT',
        'OPTIONS',
        'TRACE',
    ])] string $method): static
    {
        $this->method = $method;
        if ($method === 'POST') {
            // Disable cache on POST requests, disable follow location too as it would convert POST into GET requests
            $this->cache_timeout   = 0;
            $this->follow_location = false;
        }

        return $this;
    }


    /**
     * Set cURL options directly
     *
     * @param int   $option
     * @param mixed $value
     *
     * @return $this
     */
    public function setOpt(int $option, mixed $value): static
    {
        curl_setopt($this->curl, $option, $value);

        return $this;
    }


    /**
     * Returns if cURL will be verbose or not
     *
     * @return bool
     */
    public function getVerbose(): bool
    {
        return $this->verbose;
    }


    /**
     * Sets if cURL will be verbose or not
     *
     * @param bool $verbose
     *
     * @return static
     */
    public function setVerbose(bool $verbose): static
    {
        $this->verbose = $verbose;

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
     *
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
     *
     * @return static
     */
    public function setUserAgent(?string $user_agent): static
    {
        $this->user_agent = $user_agent;

        return $this;
    }


    /**
     * Returns the restrictions for curl output logging
     *
     * @return Restrictions|null
     */
    public function getLogRestrictions(): ?Restrictions
    {
        return $this->log_restrictions;
    }


    /**
     * Returns the path to where cURL will log. "" if logging was disabled
     *
     * @return string
     */
    public function getLogDirectory(): string
    {
        return $this->log_directory;
    }


    /**
     * Sets the path to where cURL will log. NULL or "" if logging has to be disabled
     *
     * @param string $log_directory
     * @param string $restrictions
     *
     * @return static
     */
    public function setLogDirectory(string $log_directory, string $restrictions = DIRECTORY_DATA . 'log/'): static
    {
        if ($log_directory) {
            $this->log_restrictions = Restrictions::new($restrictions, true);
            Directory::new($log_directory, $this->log_restrictions)
                     ->ensure();
        }
        $this->log_directory = $log_directory;

        return $this;
    }


    /**
     * Returns the number of retries executed for this request
     *
     * @return int
     */
    public function getRetry(): int
    {
        return $this->retry;
    }


    /**
     * Sets the maximum number of retries executed for this request
     *
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }


    /**
     * Returns the maximum number of retries executed for this request
     *
     * @param int $retries
     *
     * @return static
     */
    public function setRetries(int $retries): static
    {
        $this->retries = $retries;

        return $this;
    }


    /**
     * Sets the number of time this object will wait before retrying a failed connection
     *
     * @return int
     */
    public function getSleep(): int
    {
        return $this->sleep;
    }


    /**
     * Returns the number of time this object will wait before retrying a failed connection
     *
     * @param int $sleep
     *
     * @return static
     */
    public function setSleep(int $sleep): static
    {
        $this->sleep = $sleep;

        return $this;
    }


    /**
     * Sets the number of time in seconds before a complete request times out
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }


    /**
     * Returns the number of time in seconds before a complete request times out
     *
     * @param int $timeout
     *
     * @return static
     */
    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }


    /**
     * Sets the number of time in seconds before a connection times out
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connect_timeout;
    }


    /**
     * Returns the number of time in seconds before a connection times out
     *
     * @param int $connect_timeout
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     * Sets the cookies that will be sent for this request
     *
     * @param array $cookies
     *
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
     *
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
     *
     * @return static
     */
    public function addCookie(string $cookie): static
    {
        $this->cookies[] = $cookie;

        return $this;
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
     * Sets if object will use local cache for this request
     *
     * @return int
     */
    public function getCacheTimeout(): int
    {
        return $this->cache_timeout;
    }


    /**
     * Returns if object will use local cache for this request
     *
     * @param int $cache_timeout
     *
     * @return static
     */
    public function setCacheTimeout(int $cache_timeout): static
    {
        $this->cache_timeout = $cache_timeout;

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
     *
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
     *
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
     * Sets other extra cURL options
     *
     * @param array $options
     *
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
     *
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
     * @param int   $key
     * @param mixed $value
     *
     * @return static
     */
    public function addOption(int $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
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
     * Returns the result headers
     *
     * @return array|null
     */
    public function getRequestHeaders(): ?array
    {
        return $this->request_headers;
    }


    /**
     * Sets the request headers
     *
     * @param array $headers
     *
     * @return static
     */
    public function setRequestHeaders(array $headers): static
    {
        return $this->clearRequestHeaders()
                    ->addRequestHeaders($headers);
    }


    /**
     * Returns the result headers
     *
     * @param array $headers
     *
     * @return static
     */
    public function addRequestHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $this->addRequestHeader($key, $value);
            } else {
                $this->addRequestHeader($key, $value);
            }
        }

        return $this;
    }


    /**
     * Returns the result headers
     *
     * @param string                $key
     * @param string|float|int|null $value
     *
     * @return static
     */
    public function addRequestHeader(string $key, string|float|int|null $value): static
    {
        $this->request_headers[$key] = $value;

        return $this;
    }


    /**
     * Clears the request headers
     *
     * @return static
     */
    public function clearRequestHeaders(): static
    {
        $this->request_headers = [];

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
     * Returns the HTTP code for the request
     *
     * @note Returns NULL if the request has not yet been executed or completed.
     * @return int|null
     */
    public function getHttpCode(): ?int
    {
        return get_null((int) $this->result_status['http_code']);
    }


    /**
     * Executes the request
     *
     * @return static
     */
    abstract public function execute(): static;


    /**
     * Prepare the request
     *
     * @return void
     */
    abstract protected function prepare(): void;
}