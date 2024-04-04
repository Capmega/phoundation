<?php

declare(strict_types=1);

namespace Phoundation\Network\Curl;

use Exception;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\File;
use Phoundation\Network\Curl\Exception\Curl404Exception;
use Phoundation\Network\Curl\Exception\CurlGetException;
use Phoundation\Network\Curl\Exception\CurlNon200Exception;
use Phoundation\Network\Interfaces;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Class Curl
 *
 * This class manages Curl GET request functionality
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */
class Get extends Curl
{
    /**
     * Get class constructor
     *
     * @param Stringable|string|null $url
     */
    public function __construct(Stringable|string|null $url = null)
    {
        $this->method          = 'GET';
        $this->follow_location = true;

        parent::__construct($url);
    }


    /**
     * Executes the request
     *
     * @return static
     */
    public function execute(): static
    {
        // Use local cache?
        if ($this->cache_timeout) {
            $return = sql()->getColumn('SELECT `data` 
                                              FROM   `network_curl_cache` 
                                              WHERE  `url` = :url 
                                              AND    `created_on` + :cache < NOW()', [
                ':url'   => $this->url,
                ':cache' => $this->cache_timeout,
            ]);

            if ($return) {
                $this->retry       = 0;
                $this->result_data = Json::decode($return);
                return $this;
            }
        }

        // Prepare the request
        $this->prepare();

        try {
            $data = curl_exec($this->curl);

            // Split data from headers
            $this->result_data    = Strings::from($data, "\r\n\r\n");
            $data                 = Strings::until($data, "\r\n\r\n");
            $this->result_headers = explode(PHP_EOL, $data);
            $this->result_status  = curl_getinfo($this->curl);

            if (curl_errno($this->curl)) {
                // Oops... cURL request failed!
                throw CurlGetException::new(tr('The cURL request ":url" failed with error ":errno" ":error"', [
                    ':url'   => $this->url,
                    ':errno' => curl_errno($this->curl),
                    ':error' => curl_error($this->curl),
                ]))
                                      ->addData([
                                                    'headers' => $this->result_headers,
                                                    'data'    => $this->result_data,
                                                    'info'    => $this->result_status,
                                                ])
                                      ->setCode('CURL' . curl_errno($this->curl));
            }

            $this->checkForHttpException();

        } catch (Exception $e) {
            $this->checkForHttpException();
            $this->checkForCurlException($e);

            throw new CurlGetException(tr('Failed to make ":method" request for url ":url"', [
                ':url'    => $this->url,
                ':method' => $this->method,
            ]),                        $e);
        }

        // Do we log?
        if ($this->log_directory) {
            // We log!
            Log::notice(tr('cURL result status:'));

            $this->result_status = curl_getinfo($this->curl);

            foreach ($this->result_status as $key => $value) {
                Log::notice(CliColor::apply($key . ' : ', 'white') . Strings::force($value));
            }
        }

        if ($this->get_cookies) {
            // get cookies
            preg_match('/^Set-Cookie:\s*([^;]*)/mi', $this->result_data, $matches);

            if (empty($matches[1])) {
                $this->result_cookies = [];

            } else {
                parse_str($matches[1], $this->result_cookies);
            }
        }

        if ($this->close) {
            // Close this cURL session
            if (!empty($this->cookie_file)) {
                File::new($this->cookie_file, DIRECTORY_DATA . 'curl/')->delete();
            }

            unset($this->cookie_file);
            curl_close($this->curl);
        }

        if ($this->cache_timeout) {
            // Store the request results in cache
            unset($this->curl);

            sql()->delete('network_curl_cache', [
                'url' => $this->url,
            ]);

            sql()->insert('network_curl_cache', [
                'created_by' => Session::getUser()->getId(),
                'url'        => $this->url,
                'data'       => Json::encode($this->result_data),
                'headers'    => Json::encode($this->result_headers),
            ]);
        }

        if ($this->save_to_file) {
            file_put_contents($this->save_to_file, $this->result_data);
        }

        $this->retry = 0;
        return $this;
    }


    /**
     * Prepare the cURL request
     *
     * @return void
     */
    protected function prepare(): void
    {
        if (empty($this->url)) {
            throw new OutOfBoundsException('No URL or existing cURL connection specified');
        }

        // Prepare headers
        if (!$this->request_headers) {
            // Send default headers. Check if we're sending files. If so, use multipart
            if (empty($multipart)) {
                $this->addRequestHeaders([
                                             'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                                             'Cache-Control: max-age=0',
                                             'Connection: keep-alive',
                                             'Keep-Alive: 300',
                                             'Expect:',
                                             'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                                             'Accept-Language: en-us,en;q=0.5',
                                         ]);

            } else {
                $this->result_headers = [
                    'Content-Type: multipart/form-data',
                    'boundary={-0-0-0-0-0-(00000000000000000000)-0-0-0-0-0-}',
                    'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                    'Cache-Control: max-age=0',
                    'Connection: keep-alive',
                    'Keep-Alive: 300',
                    'Expect:',
                    'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                    'Accept-Language: en-us,en;q=0.5',
                ];
            }
        }

        // Set general options
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_REFERER, $this->referer);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->getUserAgent());
        curl_setopt($this->curl, CURLOPT_INTERFACE, Interfaces::getRandomInterfaceIp());
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, ($this->verify_ssl ? 2 : 0));
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($this->curl, CURLOPT_VERBOSE, $this->verbose);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, ($this->follow_location));
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, ($this->follow_location ? 50 : null));
        curl_setopt($this->curl, CURLOPT_POST, false);
        //curl_setopt($this->curl, CURLOPT_HTTPHEADER    , true);

        // Log cURL request?
        if ($this->log_directory) {
            curl_setopt($this->curl, CURLOPT_STDERR, File::new($this->log_directory . getmypid(), $this->log_restrictions)->open(EnumFileOpenMode::writeOnlyAppend)->getStream());

            Log::action(tr('Preparing ":method" cURL request to ":url"', [
                ':method' => $this->method,
                ':url'    => $this->url,
            ]));
        }

        if ($this->user_password) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $this->user_password);
        }

        // Use cookies?
        if (isset_get($this->cookies)) {
            if (!isset_get($this->cookie_file)) {
                $this->cookie_file = File::getTemporary()->getPath();
            }

            // Make sure the specified cookie path exists
            Directory::new(dirname($this->cookie_file))->ensure();

            // Set cookie options
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
        }

//            if ($params['utf8']) {
//                /*
//                 * Set UTF8 transfer header
//                 */
////application/x-www-form-urlencoded
//                $this->result_headers[] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['encoding']['charset'].';';
//                $this->result_headers[] = 'Content-Type: application/x-www-form-urlencoded; charset='.$_CONFIG['encoding']['charset'].';';
//                $this->result_headers[] = 'Content-Type: text/html; charset='.strtolower($_CONFIG['encoding']['charset']).';';
//            }

        // Disable DNS cache?
        if (!$this->dns_cache) {
            curl_setopt($this->curl, CURLOPT_DNS_CACHE_TIMEOUT, 0);
        }

        // Apply other cURL options
        if ($this->options) {
            foreach ($this->options as $key => $value) {
                curl_setopt($this->curl, $key, $value);
            }
        }
    }

    /**
     * Throws an exception for a variety of non 200 HTTP codes
     *
     * @return static
     */
    protected function checkForHttpException(): static
    {
        switch ($this->getHttpCode()) {
            case 200:
                // no break;
            case 304:
                // All is fine!
                return $this;

            case 400:
                throw new Curl404Exception(tr('Curl got "400 - Bad Request" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 401:
                throw new Curl404Exception(tr('Curl got "401 - Unauthorized" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 403:
                throw new Curl404Exception(tr('Curl got "403 - Forbidden" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 404:
                throw new Curl404Exception(tr('Curl got "404 - Not Found" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 410:
                throw new Curl404Exception(tr('Curl got "410 - Gone" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 500:
                throw new Curl404Exception(tr('Curl got "500 - Internal Server Errror" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            case 503:
                throw new Curl404Exception(tr('Curl got "503 - Service Unavailable" for URL ":url"', [
                    ':url' => $this->url,
                ]));

            default:
                throw new CurlNon200Exception(tr('Curl got "HTTP :http" for URL ":url"', [
                    ':http' => $this->getHttpCode(),
                    ':url'  => $this->url,
                ]));
        }
    }

    /**
     * Throws an exception for a variety of cURL error codes
     *
     * @param Throwable $e
     *
     * @return static
     */
    protected function checkForCurlException(Throwable $e): static
    {
        if (++$this->retry <= $this->retries) {
            switch ($e->getCode()) {
                case 'CURL0':
                    // no break;

                case 'CURL28':
                    // For whatever reason, connection gave HTTP code 0 which probably means that the server died
                    // off during connection. This again may mean that the server overloaded. Wait for a few
                    // seconds, and try again for a limited number of times
                    Log::warning(tr('Got HTTP0 for url ":url" at attempt ":retry" with ":connect_timeout" seconds connect timeout', [
                        ':url'             => $this->url,
                        ':retry'           => $this->retry,
                        ':connect_timeout' => $this->connect_timeout,
                    ]));

                    usleep($this->sleep);
                    return $this->execute();

                case 'CURL92':
                    // This server apparently doesn't support anything beyond HTTP1.1
                    Log::warning(tr('Got HTTP92 for url ":url" at attempt ":retry", forcing protocol HTTP 1.1 to fix', [
                        ':url'             => $this->url,
                        ':retry'           => $this->retry,
                        ':connect_timeout' => $this->connect_timeout,
                    ]));

                    $this->http_version = CURL_HTTP_VERSION_1_1;
                    return $this->execute();
            }
        }

        return $this;
    }
}
