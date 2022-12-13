<?php

namespace Phoundation\Network\Curl;

use Exception;
use Phoundation\Cli\Color;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
use Phoundation\Network\Exception\NetworkException;
use Phoundation\Network\Interfaces;
use Phoundation\Utils\Json;



/**
 * Class Curl
 *
 * This class manages Curl GET request functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Get extends Curl
{
    /**
     * Get class constructor
     *
     * @param string|null $url
     */
    public function __construct(?string $url = null)
    {
        $this->method          = 'GET';
        $this->follow_location = true;

        parent::__construct($url);
    }



    /**
     * Executes the GET request
     *
     * @return void
     */
    public function execute(): void
    {
        // Use local cache?
        if ($this->cache) {
            $return = sql()->getColumn('SELECT `data` 
                                              FROM   `network_curl_cache` 
                                              WHERE  `url` = :url 
                                              AND    `created_on` + :cache < NOW()', [
                ':url'   => $this->url,
                ':cache' => $this->cache
            ]);

            if ($return) {
                $this->retry = 0;
                $this->result_data = Json::decode($return);
                return;
            }
        }

        // Prepare the request
        $this->prepare();

        try {
            $data = curl_exec($this->curl);

            $this->result_data    = $data['data'];
            $this->result_headers = $data['headers'];

            if (curl_errno($this->curl)) {
                throw new NetworkException(tr('CURL failed with ":e"', [
                    ':e' => curl_strerror(curl_errno($this->curl))
                ]));
            }
        }catch(Exception $e) {
            if ((($e->getCode() == 'HTTP0') or ($e->getCode() == 'CURL28')) and (++$this->retry <= $this->retries)) {
                // For whatever reason, connection gave HTTP code 0 which probably means that the server died off
                // during connection. This again may mean that the server overloaded. Wait for a few seconds, and try
                // again for a limited number of times
                Log::warning(tr('Got HTTP0 for url ":url" at attempt ":retry" with ":connect_timeout" seconds connect timeout', [
                    ':url'             => $this->url,
                    ':retry'           => $this->retry,
                    ':connect_timeout' => $this->connect_timeout
                ]));

                usleep($this->sleep);
                $this->execute();
                return;
            }

            throw new NetworkException(tr('Failed to make ":method" request for url ":url"', [
                ':url'    => $this->url,
                ':method' => $this->method
            ]), $e);
        }

        // Do we log?
        if ($this->log_path) {
            // We log!
            Log::notice(tr('cURL result status:'));

            $this->result_status = curl_getinfo($this->curl);

            foreach ($this->result_status as $key => $value) {
                Log::notice(Color::apply($key.' : ', 'white') . Strings::force($value));
            }
        }

        $this->result_status = curl_getinfo($this->curl);

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
                File::new($this->cookie_file, PATH_DATA . 'curl/')->delete();
            }

            unset($this->cookie_file);
            curl_close($this->curl);
        }

        if ($this->cache) {
            // Store the request results in cache
            unset($this->curl);

            sql()->delete('network_curl_cache', [
                'url' => $this->url
            ]);

            sql()->insert('network_curl_cache', [
                'created_by' => Session::getUser()->getId(),
                'url'        => $this->url,
                'data'       => Json::encode($this->result_data),
                'headers'    => Json::encode($this->result_headers)
            ]);
        }

        switch ($this->result_status['http_code']) {
            case 200:
                break;

            case 403:
                $data = Json::decode($this->result_data);

                throw new NetworkException(tr('URL ":url" gave HTTP "403" ACCESS DENIED because ":data"', [
                    ':url' => $this->url,
                    ':data' => $data
                ]));

            case 404:
                $data = Json::decode($this->result_data);

                throw new NetworkException(tr('URL ":url" gave HTTP "404" NOT FOUND because ":data"', [
                    ':url' => $this->url,
                    ':data' => $data
                ]));

            default:
                throw new NetworkException(tr('URL ":url" gave HTTP ":httpcode"', [
                    ':url'      => $this->url,
                    ':httpcode' => $this->result_status['http_code']
                ]));
        }

        if ($this->save_to_file) {
            file_put_contents($this->save_to_file, $this->result_data);
        }
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

        $this->retry = 0;

        if (isset($this->curl)) {
            // Update only the URL
            curl_setopt($this->curl, CURLOPT_URL, $this->url);
        } else {
            // Setup new cURL request
            $this->curl = curl_init();

            // Prepare headers
            if ($this->result_headers === null) {
                // Send default headers. Check if we're sending files. If so, use multipart
                if (empty($multipart)) {
                    $this->result_headers = [
                        'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                        'Cache-Control: max-age=0',
                        'Connection: keep-alive',
                        'Keep-Alive: 300',
                        'Expect:',
                        'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                        'Accept-Language: en-us,en;q=0.5'
                    ];

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
                        'Accept-Language: en-us,en;q=0.5'
                    ];
                }
            }

            // Set general options
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_URL           , $this->url);
            curl_setopt($this->curl, CURLOPT_REFERER       , $this->referer);
            curl_setopt($this->curl, CURLOPT_USERAGENT     , $this->getUserAgent());
            curl_setopt($this->curl, CURLOPT_INTERFACE     , Interfaces::getRandomIp());
            curl_setopt($this->curl, CURLOPT_TIMEOUT       , $this->timeout);
            curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, ($this->verify_ssl ? 2 : 0));
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, ($this->verify_ssl ? 1 : 0));
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST , $this->method);
            curl_setopt($this->curl, CURLOPT_VERBOSE       , $this->verbose);
            curl_setopt($this->curl, CURLOPT_HEADER        , true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, ($this->follow_location ? 1 : 0));
            curl_setopt($this->curl, CURLOPT_MAXREDIRS     , ($this->follow_location ? 50 : null));
            curl_setopt($this->curl, CURLOPT_POST          , false);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER    , true);

            // Log cURL request?
            if ($this->log_path) {
                curl_setopt($this->curl, CURLOPT_STDERR, File::new($this->log_path)->open('a'));

                Log::action(tr('Preparing ":method" cURL request to ":url"', [
                    ':url' => $this->url,
                ]));
            }

            if ($this->user_password) {
                curl_setopt($this->curl, CURLOPT_USERPWD, $this->user_password);
            }

            // Use cookies?
            if (isset_get($this->cookies)) {
                if (!isset_get($this->cookie_file)) {
                    $this->cookie_file = Filesystem::createTempFile()->getFile();
                }

                // Make sure the specified cookie path exists
                Path::new(dirname($this->cookie_file))->ensure();

                // Set cookie options
                curl_setopt($this->curl, CURLOPT_COOKIEJAR    , $this->cookie_file);
                curl_setopt($this->curl, CURLOPT_COOKIEFILE   , $this->cookie_file);
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
    }
}