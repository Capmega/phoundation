<?php

declare(strict_types=1);

namespace Phoundation\Network\Curl\Interfaces;


use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Filesystem\Restrictions;
use Stringable;

/**
 * Class Curl
 *
 * This class manages the basic Curl functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
interface CurlInterface
{
    /**
     * Returns the url
     *
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * Sets the url
     *
     * @param Stringable|string|null $url
     * @return static
     */
    public function setUrl(Stringable|string|null $url): static;

    /**
     * Returns the request method
     *
     * @return int
     */
    public function getHttpVersion(): int;

    /**
     * Sets the request method
     *
     * @param int $http_version
     * @return static
     */
    public function setHttpVersion(int $http_version): static;

    /**
     * Returns the request method
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Sets the request method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): static;

    /**
     * Returns if cURL will be verbose or not
     *
     * @return bool
     */
    public function getVerbose(): bool;

    /**
     * Sets if cURL will be verbose or not
     *
     * @param bool $verbose
     * @return static
     */
    public function setVerbose(bool $verbose): static;

    /**
     * Returns if the request will follow page redirects
     *
     * @return bool
     */
    public function getFollowLocation(): bool;

    /**
     * Sets if the request will follow page redirects
     *
     * @param bool $follow_location
     * @return static
     */
    public function setFollowLocation(bool $follow_location): static;

    /**
     * Returns the user agent to be used for this request
     *
     * @return string
     */
    public function getUserAgent(): string;

    /**
     * Sets the user agent to be used for this request
     *
     * @param string|null $user_agent
     * @return static
     */
    public function setUserAgent(?string $user_agent): static;

    /**
     * Returns the path to where cURL will log. "" if logging was disabled
     *
     * @return string
     */
    public function getLogDirectory(): string;

    /**
     * Returns the restrictions for curl output logging
     *
     * @return Restrictions|null
     */
    public function getLogRestrictions(): ?Restrictions;

    /**
     * Sets the path to where cURL will log. NULL or "" if logging has to be disabled
     *
     * @param string $log_directory
     * @param string $restrictions
     * @return static
     */
    public function setLogDirectory(string $log_directory, string $restrictions = DIRECTORY_DATA . 'log/'): static;

    /**
     * Returns the amount of retries executed for this request
     *
     * @return int
     */
    public function getRetry(): int;

    /**
     * Sets the maximum amount of retries executed for this request
     *
     * @return int
     */
    public function getRetries(): int;

    /**
     * Returns the maximum amount of retries executed for this request
     *
     * @param int $retries
     * @return static
     */
    public function setRetries(int $retries): static;

    /**
     * Sets the amount of time this object will wait before retrying a failed connection
     *
     * @return int
     */
    public function getSleep(): int;

    /**
     * Returns the amount of time this object will wait before retrying a failed connection
     *
     * @param int $sleep
     * @return static
     */
    public function setSleep(int $sleep): static;

    /**
     * Sets the amount of time in seconds before a complete request times out
     *
     * @return int
     */
    public function getTimeout(): int;

    /**
     * Returns the amount of time in seconds before a complete request times out
     *
     * @param int $timeout
     * @return static
     */
    public function setTimeout(int $timeout): static;

    /**
     * Sets the amount of time in seconds before a connection times out
     *
     * @return int
     */
    public function getConnectTimeout(): int;

    /**
     * Returns the amount of time in seconds before a connection times out
     *
     * @param int $connect_timeout
     * @return static
     */
    public function setConnectTimeout(int $connect_timeout): static;

    /**
     * Returns the cURL interface
     *
     * @return mixed
     */
    public function getCurl(): mixed;

    /**
     * Returns if the request should use multipart or not
     *
     * @return bool
     */
    public function getMultipart(): bool;

    /**
     * Sets if the request should use multipart or not
     *
     * @param bool $multipart
     * @return static
     */
    public function setMultipart(bool $multipart): static;

    /**
     * Returns if the connection will be closed once the request has been completed
     *
     * @return bool
     */
    public function getClose(): bool;

    /**
     * Returns if the connection will be closed once the request has been completed
     *
     * @param bool $close
     * @return static
     */
    public function setClose(bool $close): static;

    /**
     * Returns if the DNS should use cache or not
     *
     * @return bool
     */
    public function getDnsCache(): bool;

    /**
     * Sets if the DNS should use cache or not
     *
     * @param bool $dns_cache
     * @return static
     */
    public function setDnsCache(bool $dns_cache): static;

    /**
     * Returns if the request will verify the SSL certificate or not
     *
     * @return bool
     */
    public function getVerifySsl(): bool;

    /**
     * Sets if the request will verify the SSL certificate or not
     *
     * @param bool $verify_ssl
     * @return static
     */
    public function setVerifySsl(bool $verify_ssl): static;

    /**
     * Sets if object will store and use cookies
     *
     * @return bool
     */
    public function getGetCookies(): bool;

    /**
     * Returns if object will store and use cookies
     *
     * @param bool $get_cookies
     * @return static
     */
    public function setGetCookies(bool $get_cookies): static;

    /**
     * Sets what file cookies should be written to
     *
     * @return string
     */
    public function getCookieFile(): string;

    /**
     * Returns if object will store and use cookies
     *
     * @param string $cookie_file
     * @return static
     */
    public function setCookieFile(string $cookie_file): static;

    /**
     * Returns the cookies that were sent by the remote server for this request
     *
     * @return array
     */
    public function getResultCookies(): array;

    /**
     * Returns the cookies that will be sent for this request
     *
     * @return array
     */
    public function getCookies(): array;

    /**
     * Clears the cookies that will be sent for this request
     *
     * @return static
     */
    public function clearCookies(): static;

    /**
     * Sets the cookies that will be sent for this request
     *
     * @param array $cookies
     * @return static
     */
    public function setCookies(array $cookies): static;

    /**
     * Adds the specified cookies that will be sent for this request
     *
     * @param array $cookies
     * @return static
     */
    public function addCookies(array $cookies): static;

    /**
     * Adds the specified cookie that will be sent for this request
     *
     * @param string $cookie
     * @return static
     */
    public function addCookie(string $cookie): static;

    /**
     * Sets if object will use local cache for this request
     *
     * @return int
     */
    public function getCacheTimeout(): int;

    /**
     * Returns if object will use local cache for this request
     *
     * @param int $cache_timeout
     * @return static
     */
    public function setCacheTimeout(int $cache_timeout): static;

    /**
     * Returns the user_password header
     *
     * @return string
     */
    public function getReferer(): string;

    /**
     * Sets the user_password header
     *
     * @param string $user_password
     * @return static
     */
    public function setReferer(string $user_password): static;

    /**
     * Returns the file to which the result will be saved
     *
     * @return string
     */
    public function getSaveToFile(): string;

    /**
     * Sets the file to which the result will be saved
     *
     * @param string $save_to_file
     * @return static
     */
    public function setSaveToFile(string $save_to_file): static;

    /**
     * Returns other extra cURL options
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Clears other extra cURL options
     *
     * @return static
     */
    public function clearsOptions(): static;

    /**
     * Sets other extra cURL options
     *
     * @param array $options
     * @return static
     */
    public function setOptions(array $options): static;

    /**
     * Adds other extra cURL options
     *
     * @param array $options
     * @return static
     */
    public function addOptions(array $options): static;

    /**
     * Adds another extra cURL option
     *
     * @param int $key
     * @param mixed $value
     * @return static
     */
    public function addOption(int $key, mixed $value): static;

    /**
     * Returns the result headers
     *
     * @return array|null
     */
    public function getRequestHeaders(): ?array;

    /**
     * Sets the request headers
     *
     * @param array $headers
     * @return static
     */
    public function setRequestHeaders(array $headers): static;

    /**
     * Returns the result headers
     *
     * @param array $headers
     * @return static
     */
    public function addRequestHeaders(array $headers): static;

    /**
     * Returns the result headers
     *
     * @param string $key
     * @param string|float|int|null $value
     * @return static
     */
    public function addRequestHeader(string $key, string|float|int|null $value): static;

    /**
     * Clears the request headers
     *
     * @return static
     */
    public function clearRequestHeaders(): static;

    /**
     * Returns the result headers
     *
     * @return array|null
     */
    public function getResultHeaders(): ?array;

    /**
     * Returns the result status
     *
     * @return array|null
     */
    public function getResultStatus(): ?array;

    /**
     * Returns the result data
     *
     * @return array|null
     */
    public function getResultData(): ?string;

    /**
     * Executes the request
     *
     * @return static
     */
    public function execute(): static;
}
