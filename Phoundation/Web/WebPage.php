<?php

namespace Phoundation\Web;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Api\ApiInterface;
use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Notifications\Notification;
use Phoundation\Servers\Server;
use Phoundation\Web\Exception\WebException;
use Phoundation\Web\Http\Exception\HttpException;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\Html\Template\TemplatePage;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;



/**
 * Class Page
 *
 * This class contains methods to assist in building web pages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class WebPage
{
    /**
     * Singleton
     *
     * @var WebPage $instance
     */
    protected static WebPage $instance;

    /**
     * The server filesystem restrictions
     *
     * @var Server $server_restrictions
     */
    protected static Server $server_restrictions;

    /**
     * The template class that builds the UI
     *
     * @var TemplatePage $template_page
     */
    protected static TemplatePage $template_page;

    /**
     * The Phoundation API interface
     *
     * @var ApiInterface $api_interface
     */
    protected static ApiInterface $api_interface;

    /**
     * The flash object for this user
     *
     * @var Flash|null
     */
    protected static ?Flash $flash = null;

    /**
     * Tracks if self::sendHeaders() sent headers already or not.
     *
     * @note IMPORTANT: Since flush() and ob_flush() will NOT lock headers until the buffers are actually flushed, and
     *                  they will neither actually flush the buffers as long as the process is running AND the buffers
     *                  are not full yet, weird things can happen. With a buffer of 4096 bytes (typically), echo 100
     *                  characters, and then execute self::sendHeaders(), then ob_flush() and flush() and headers_sent()
     *                  will STILL be false, and REMAIN false until the buffer has reached 4096 characters OR the
     *                  process ends. This variable just keeps track if self::sendHeaders() has been executed (and it
     *                  won't execute again), but headers might still be sent out manually. This is rather messed up,
     *                  because it really shows as if information was sent, the buffers are flushed, yet nothing is
     *                  actually flushed, so the headers are also not sent. This is just messed up PHP.
     * @var bool $http_headers_sent
     */
    protected static bool $http_headers_sent = false;

    /**
     * The client specified ETAG for this request
     *
     * @var string|null $etag
     */
    protected static ?string $etag = null;

    /**
     * !DOCTYPE variable
     *
     * @var string
     */
    protected static string $doctype = 'html';

    /**
     * The page title
     *
     * @var string|null $title
     */
    protected static ?string $title = null;

    /**
     * Information that goes into the HTML header
     *
     * @var array $headers
     */
    protected static array $headers = [
        'link'       => [],
        'meta'       => [],
        'javascript' => []
    ];

    /**
     * Information that goes into the HTML footer
     *
     * @var array $footers
     */
    protected static array $footers = [
        'javascript' => []
    ];

    /**
     * The files that should be added in the header
     *
     * @var array
     */
    protected static array $header_files = [];

    /**
     * The files that should be added in the footer
     *
     * @var array
     */
    protected static array $footer_files = [];

    /**
     * The HTML buffer for this page
     *
     * @var string $html
     */
    protected static string $html = '';

    /**
     * The unique hash for this page
     *
     * @var string|null $hash
     */
    protected static ?string $hash = null;

    /**
     * Keeps track on if the HTML headers have been sent / generated or not
     *
     * @var bool $html_headers_sent
     */
    protected static bool $html_headers_sent = false;

    /**
     * The status code that will be returned to the client
     *
     * @var int $http_code
     */
    protected static int $http_code = 200;

    /**
     * The list of meta data that the client accepts
     *
     * @var array|null $accepts
     */
    protected static ?array $accepts = null;

    /**
     * CORS headers
     *
     * @var array $cors
     */
    protected static array $cors = [];

    /**
     * Content-type header
     *
     * @var string|null $content_type
     */
    protected static ?string $content_type = null;



    /**
     * Page class constructor
     *
     * @throws Exception
     */
    protected function __construct()
    {
        self::$headers['meta']['charset']  = ['charset'  => Config::get('languages.encoding.charset', 'UTF-8')];
        self::$headers['meta']['viewport'] = ['viewport' => Config::get('web.viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no')];
    }



    /**
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): WebPage
    {
        if (!isset(self::$instance)) {
            self::$instance = new WebPage();
        }

        return self::$instance;
    }


    
    /**
     * Returns the current tab index and automatically increments it
     *
     * @return Server
     */
    public static function getServerRestrictions(): Server
    {
        return self::$server_restrictions;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param Server $server_restrictions
     * @return static
     */
    public static function setServerRestrictions(Server $server_restrictions): static
    {
        self::$server_restrictions = $server_restrictions;
        return self::getInstance();
    }



    /**
     * Returns the current TemplatePage used for this page
     *
     * @return TemplatePage
     */
    public static function getTemplatePage(): TemplatePage
    {
        return self::$template_page;
    }



    /**
     * Returns true if the HTTP headers have been sent
     *
     * @return bool
     */
    public static function getHttpHeadersSent(): bool
    {
        return self::$http_headers_sent;
    }



    /**
     * Returns the status code that will be sent to the client
     *
     * @return int
     */
    public static function getHttpCode(): int
    {
        return self::$http_code;
    }



    /**
     * Sets the status code that will be sent to the client
     *
     * @param int $code
     * @return static
     */
    public static function setHttpCode(int $code): static
    {
        // Validate status code
        // TODO implement

        self::$http_code = $code;
        return self::getInstance();
    }



    /**
     * Returns the mimetype / content type
     *
     * @return string|null
     */
    public static function getContentType(): ?string
    {
        return self::$content_type;
    }



    /**
     * Sets the mimetype / content type
     *
     * @param string $content_type
     * @return static
     */
    public static function setContentType(string $content_type): static
    {
        // Validate status code
        // TODO implement

        self::$content_type = $content_type;
        return self::getInstance();
    }



    /**
     * Returns the CORS headers
     *
     * @return array
     */
    public static function getCors(): array
    {
        return self::$cors;
    }


    /**
     * Sets the status code that will be sent to the client
     *
     * @param string $origin
     * @param string $methods
     * @param string $headers
     * @return void
     */
    public static function setCors(string $origin, string $methods, string $headers): void
    {
        // Validate CORS data
        // TODO implement validation

        self::$cors = [
            'origin'  => '*.',
            'methods' => 'GET, POST',
            'headers' => ''
        ];
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return string
     */
    public static function getDocType(): string
    {
        return self::$doctype;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param string $doctype
     * @return static
     */
    public static function setDoctype(string $doctype): static
    {
        self::$doctype = $doctype;
        return self::getInstance();
    }



    /**
     * Returns the page title
     *
     * @return string
     */
    public static function getTitle(): string
    {
        return self::$title;
    }



    /**
     * Sets the page title
     *
     * @param string $title
     * @param bool $no_translate
     * @return static
     */
    public static function setTitle(string $title, bool $no_translate = false): static
    {
        self::$title = $title;
        return self::getInstance();
    }



    /**
     * Returns the page charset
     *
     * @return string|null
     */
    public static function getCharset(): ?string
    {
        return isset_get(self::$headers['meta']['charset']);
    }



    /**
     * Sets the page charset
     *
     * @param string|null $charset
     * @return static
     */
    public static function setCharset(?string $charset): static
    {
        self::$headers['meta']['charset'] = $charset;
        return self::getInstance();
    }



    /**
     * Returns the page viewport
     *
     * @return string|null
     */
    public static function getViewport(): ?string
    {
        return isset_get(self::$headers['meta']['viewport']);
    }



    /**
     * Sets the page viewport
     *
     * @param string|null $viewport
     * @return static
     */
    public static function setViewport(?string $viewport): static
    {
        self::$headers['meta']['viewport'] = $viewport;
        return self::getInstance();
    }



    /**
     * Process the routed target
     *
     * We have a target for the requested route. If the resource is a PHP page, then
     * execute it. Anything else, send it directly to the client
     *
     * @see Route::execute()
     * @see Templateself::execute()
     *
     * @param string $target The target file that should be executed or sent to the client
     * @param Template|null $template
     * @param boolean $attachment If specified as true, will send the file as a downloadable attachement, to be written
     *                            to disk instead of displayed on the browser. If set to false, the file will be sent as
     *                            a file to be displayed in the browser itself.
     * @return string|null
     */
    public static function execute(string $target, ?Template $template = null, bool $attachment = false): ?string
    {
        try {
            if (Strings::fromReverse(dirname($target), '/') === 'system') {
                // Wait a small random time to avoid timing attacks on system pages
                usleep(mt_rand(1, 500));
            }

            // Do we have access to this page?
            self::$server_restrictions->checkRestrictions($target, false);

            // Set the page hash
            self::$hash = sha1($_SERVER['REQUEST_URI']);

            // Do we have a cached version available?
            $cache = Cache::read(self::$hash, 'pages');

            if ($cache) {
                $length = self::sendHttpHeaders($cache['headers']);
                Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);

                // Send the page to the client
                self::send($cache['output']);
            }

            Core::writeRegister($target, 'system', 'script_file');
            ob_start();

            // Execute the specified target
            switch (Core::getCallType()) {
                case 'api':
                    // no-break
                case 'ajax':
                    self::$api_interface = new ApiInterface();
                    $output = self::$api_interface->execute($target);
                    break;

                default:
                    if (!$template) {
                        if (!self::$template_page) {
                            throw new OutOfBoundsException(tr('Cannot execute page ":target", no Template specified or available', [
                                ':target' => $target
                            ]));
                        }
                    } else {
                        // Get a new template page from the specified template
                        self::$template_page = $template->getTemplatePage();
                    }

                    // Execute the file and send the output HTML as a web page
                    Log::action(tr('Executing page ":target" with template ":template" in language ":language" and sending output as HTML web page', [
                        ':target'   => Strings::from($target, PATH_ROOT),
                        ':template' => $template->getName(),
                        ':language' => LANGUAGE
                    ]));

                    $output = self::$template_page->execute($target);
            };

            // TODO Work on the HTTP headers, lots of issues here still, like content-length!
            // Build the headers, cache output and headers together, then send the headers
            $headers = self::buildHttpHeaders($output, $attachment);

            if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
                // HEAD request, do not send any HTML whatsoever
                $output = null;
            }

            if ($headers) {
                // Only cache if there are headers. If self::buildHeaders() returned null this means that the headers
                // have already been sent before, probably by a debugging function like Debug::show(). DON'T CACHE!
                Cache::write([
                    'output'  => $output,
                    'headers' => $headers,
                ], $target,'pages');

                $length = self::sendHttpHeaders($headers);
                Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);
            }

            switch (self::getHttpCode()) {
                case 304:
                    // 304 requests indicate the browser to use it's local cache, send nothing
                    // no-break

                case 429:
                    // 429 Tell the client that it made too many requests, send nothing
                    return null;
            }

            // Send the page to the client
            self::send($output);

        } catch (Exception $e) {
            Notification::new()
                ->setTitle(tr('Failed to execute ":type" page ":page" with language ":language"', [
                    ':type'     => Core::getCallType(),
                    ':page'     => $target,
                    ':language' => LANGUAGE
                ]))
                ->setException($e)
                ->send();

            throw $e;
        }

        die();
    }



    /**
     * Return the specified URL with a redirect URL stored in $core->register['redirect']
     *
     * @note If no URL is specified, the current URL will be used
     * @see UrlBuilder
     * @see UrlBuilder::addQueries()
     *
     * @param string|bool|null $url
     * @param int $http_code
     * @param bool $clear_session_redirect
     * @param int|null $time_delay
     * @return void
     */
    #[NoReturn] public static function redirect(string|bool|null $url = null, int $http_code = 301, bool $clear_session_redirect = true, ?int $time_delay = null): void
    {
        if (!PLATFORM_HTTP) {
            throw new WebException(tr('self::redirect() can only be called on web sessions'));
        }

        // Build URL
        $url = self::build($url)->www();

        if ($_GET['redirect']) {
            $url = self::build($url)->addQueries('redirect=' . urlencode($_GET['redirect']));
        }

        /*
         * Validate the specified http_code, must be one of
         *
         * 301 Moved Permanently
         * 302 Found
         * 303 See Other
         * 307 Temporary Redirect
         */
        switch ($http_code) {
            case 0:
                // no-break
            case 301:
                $http_code = 301;
                break;
            case 302:
                // no-break
            case 303:
                // no-break
            case 307:
                // All valid
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid HTTP code ":code" specified', [
                    ':code' => $http_code
                ]));
        }

        // ???
        if ($clear_session_redirect) {
            if (!empty($_SESSION)) {
                unset($_GET['redirect']);
                unset($_SESSION['sso_referrer']);
            }
        }

        // Redirect with time delay
        if ($time_delay) {
            Log::action(tr('Redirecting with ":time" seconds delay to url ":url"', [
                ':time' => $time_delay,
                ':url' => $url
            ]));

            header('Refresh: '.$time_delay.';'.$url, true, $http_code);
            die();
        }

        // Redirect immediately
        Log::action(tr('Redirecting to url ":url"', [':url' => $url]));
        header('Location:' . $url , true, $http_code);
        die();
    }



    /**
     * Returns requested main mimetype, or if requested mimetype is accepted or not
     *
     * The function will return true if the specified mimetype is supported, or false, if not
     *
     * @see self::acceptsLanguages()
     * code
     * // This will return true
     * $result = accepts('image/webp');
     *
     * // This will return false
     * $result = accepts('image/foobar');
     *
     * // On a browser, this typically would return text/html
     * $result = accepts();
     * /code
     *
     * This would return
     * code
     * Foo...bar
     * /code
     *
     * @param string $mimetype The mimetype that hopefully is accepted by the client
     * @return mixed True if the client accepts it, false if not
     */
    public static function accepts(string $mimetype): bool
    {
        static $headers = null;

        if (!$mimetype) {
            throw new OutOfBoundsException(tr('No mimetype specified'));
        }

        if (!$headers) {
            // Cleanup the HTTP accept headers (opera aparently puts spaces in there, wtf?), then convert them to an
            // array where the accepted headers are the keys so that they are faster to access
            $headers = isset_get($_SERVER['HTTP_ACCEPT']);
            $headers = str_replace(', ', '', $headers);
            $headers = Arrays::force($headers);
            $headers = array_flip($headers);
        }

        // Return if the client supports the specified mimetype
        return isset($headers[$mimetype]);
    }



    /**
     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list of languages / locales accepted by the HTTP client
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see accepts()
     * @note: This function is called by the startup system and its output stored in $core->register['accept_language']. There is typically no need to execute this function on any other places
     * @version 1.27.0: Added function and documentation
     *
     * @return array The list of accepted languages and locales as specified by the HTTP client
     */
    public static function acceptsLanguages(): array
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // No accept language headers were specified
            $return  = [
                '1.0' => [
                    'language' => Config::get('languages.default', 'en'),
                    'locale'   => Strings::cut(Config::get('locale.LC_ALL', 'US'), '_', '.')
                ]
            ];

        } else {
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = Arrays::force($headers, ',');
            $default = array_shift($headers);
            $return  = [
                '1.0' => [
                    'language' => Strings::until($default, '-'),
                    'locale'   => (str_contains($default, '-') ? Strings::from($default, '-') : null)
                ]
            ];

            if (empty($return['1.0']['language'])) {
                // Specified accepts language headers contain no language
                $return['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }

            if (empty($return['1.0']['locale'])) {
                // Specified accept language headers contain no locale
                $return['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
            }

            foreach ($headers as $header) {
                $requested =  Strings::until($header, ';');
                $requested =  [
                    'language' => Strings::until($requested, '-'),
                    'locale'   => (str_contains($requested, '-') ? Strings::from($requested, '-') : null)
                ];

                if (empty(Config::get('languages.supported', [])[$requested['language']])) {
                    continue;
                }

                $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($return);
        return $return;
    }



    /**
     * Returns the HTML output buffer for this page
     *
     * @return string
     */
    public static function getHtml(): string
    {
        return ob_get_contents();
    }



    /**
     * Returns the HTML unique hash
     *
     * @return string
     */
    public static function getHash(): string
    {
        return self::$hash;
    }



    /**
     * Returns if the HTML headers have been sent
     *
     * @return bool
     */
    public static function getHtmlHeadersSent(): bool
    {
        return self::$html_headers_sent;
    }



    /**
     * Returns the length HTML output buffer for this page
     *
     * @return int
     */
    public static function getContentLength(): int
    {
        return ob_get_length();
    }



    /**
     * Send the current buffer to the client
     *
     * @param string $output
     * @return void
     */
    public static function send(string $output): void
    {
        // Send output to the client
        $length = strlen($output);
        echo $output;

        ob_flush();
        flush();

        Log::success(tr('Sent ":length" bytes of HTML to client', [':length' => $length]), 4);
    }



    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @return string|null
     */
    public static function get(): ?string
    {
        return self::$template_page->get();
    }



    /**
     * Access the Flash object
     *
     * @return Flash
     */
    public static function flash(): Flash
    {
        if (!self::$flash) {
            self::$flash = new Flash();
        }

        return self::$flash;
    }



    /**
     * Add meta information
     *
     * @param array $meta
     * @return void
     */
    public static function addMeta(array $meta): void
    {
        self::$headers['meta'][] = $meta;
    }



    /**
     * Set the favicon for this page
     *
     * @param string $url
     * @return static
     */
    public static function setFavIcon(string $url): static
    {
        try {
            self::$headers['link'][$url] = [
                'rel'  => 'icon',
                'href' => Url::build($url)->img(),
                'type' => File::new(Filesystem::absolute($url, 'img'), PATH_CDN . LANGUAGE . '/img')->mimetype()
            ];
        } catch (FilesystemException $e) {
            Log::warning($e->makeWarning());
        }

        return self::getInstance();
    }



    /**
     * Load the specified javascript file(s)
     *
     * @param string|array $urls
     * @param bool|null $header
     * @return static
     */
    public static function loadJavascript(string|array $urls, ?bool $header = null): static
    {
        if ($header === null) {
            $header = !Config::getBoolean('web.javascript.delay', true);
        }

        if ($header and self::$html_headers_sent) {
            Log::warning(tr('Not adding files ":files" to HTML headers as the HTML headers have already been generated', [
                ':files' => $urls
            ]));
        }

        foreach (Arrays::force($urls, ',') as $url) {
            if ($header) {
                self::$headers['javascript'][$url] = [
                    'type' => 'text/javascript',
                    'src'  => Url::build($url)->js()
                ];

            } else {
                self::$footers['javascript'][$url] = [
                    'type' => 'text/javascript',
                    'src'  => Url::build($url)->js()
                ];
            }
        }

        return self::getInstance();
    }



    /**
     * Load the specified CSS file(s)
     *
     * @param string|array $urls
     * @return static
     */
    public static function loadCss(string|array $urls): static
    {
        foreach (Arrays::force($urls, '') as $url) {
            self::$headers['link'][$url] = [
                'rel'  => 'stylesheet',
                'href' => Url::build($url)->css(),
            ];
        }

        return self::getInstance();
    }



    /**
     * Build and return the HTML headers
     *
     * @return string|null
     */
    public static function buildHeaders(): ?string
    {
        $return = '<!DOCTYPE ' . self::$doctype . '>
        <html lang="' . Session::getLanguage() . '">' . PHP_EOL;

        if (self::$title) {
            $return .= '<title>' . self::$title . '</title>' . PHP_EOL;
        }

        foreach (self::$headers['meta'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<meta ' . $header . ' />' . PHP_EOL;
        }

        foreach (self::$headers['link'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<link ' . $header . ' />' . PHP_EOL;
        }

        foreach (self::$headers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return . '</head>';
    }



    /**
     * Build and return the HTML footers
     *
     * @return string|null
     */
    public static function buildFooters(): ?string
    {
        $return = '';

        foreach (self::$footers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"');
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return;
    }



    /**
     * Only-on type switch to indicate that the HTML headers have been generated and no more information can be added
     * to them
     *
     * @param bool $set
     * @return bool
     */
    public static function htmlHeadersSent(bool $set = false): bool
    {
        if ($set) {
            self::$html_headers_sent = true;
        }

        return self::$html_headers_sent;
    }



    /**
     * Builds and returns all the HTTP headers
     *
     * @param string $output
     * @param bool $attachment
     * @return array|null
     * @todo Refactor and remove $_CONFIG dependancies
     * @todo Refactor and remove $core dependancies
     * @todo Refactor and remove $params dependancies
     */
    public static function buildHttpHeaders(string $output, bool $attachment = false): ?array
    {
        if (self::httpHeadersSent()) {
            return null;
        }

        // Remove incorrect or insecure headers
        header_remove('Expires');
        header_remove('Pragma');

        /*
         * Ensure that from this point on we have a language configuration available
         *
         * The startup systems already configures languages but if the startup itself fails, or if a show() or showdie()
         * was issued before the startup finished, then this could leave the system without defined language
         */
        if (!defined('LANGUAGE')) {
            define('LANGUAGE', Config::get('http.language.default', 'en'));
        }

        // Create ETAG, possibly send out HTTP304 if client sent matching ETAG
        self::cacheEtag();

        // What to do with the PHP signature?
        $signature = Config::get('security.expose.php-signature', false);

        if (!$signature) {
            // Remove the PHP signature
            header_remove('X-Powered-By');

        } elseif (!is_bool($signature)) {
            // Send custom (fake) X-Powered-By header
            $headers[] = 'X-Powered-By: ' . $signature;
        }

        // Add Phoundation signature?
        if (Config::getBoolean('security.expose.phoundation-signature', false)) {
            header('X-Powered-By: Phoundation ' . Core::FRAMEWORKCODEVERSION);
        }

        $headers[] = 'Content-Type: ' . self::$content_type . '; charset=' . Config::get('languages.encoding.charset', 'UTF-8');
        $headers[] = 'Content-Language: ' . LANGUAGE;
        $headers[] = 'Content-Length: ' . strlen($output);

        if (self::$http_code == 200) {
            if (empty($params['last_modified'])) {
                $headers[] = 'Last-Modified: ' . Date::convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

            } else {
                $headers[] = 'Last-Modified: ' . Date::convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
            }
        }

        // Add noidex, nofollow and nosnipped headers for non production environments and non normal HTTP pages.
        // These pages should NEVER be indexed
        if (!Debug::production() or !Core::getCallType('http') or Config::get('web.noindex', false)) {
            $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
        }

        // CORS headers
        if (Config::get('web.security.cors', true) or self::$cors) {
            // Add CORS / Access-Control-Allow-.... headers
            // TODO This will cause issues if configured web.cors is not an array!
            self::$cors = array_merge(Arrays::force(Config::get('web.cors', [])), self::$cors);

            foreach (self::$cors as $key => $value) {
                switch ($key) {
                    case 'origin':
                        if ($value == '*.') {
                            // Origin is allowed from all subdomains
                            $origin = Strings::from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                            $length = strlen(isset_get($_SESSION['domain']));

                            if (substr($origin, -$length, $length) === isset_get($_SESSION['domain'])) {
                                // Sub domain matches. Since CORS does not support sub domains, just show the
                                // current sub domain.
                                $value = $_SERVER['HTTP_ORIGIN'];

                            } else {
                                // Sub domain does not match. Since CORS does not support sub domains, just show no
                                // allowed origin domain at all
                                $value = '';
                            }
                        }

                    // no-break

                    case 'methods':
                        // no-break
                    case 'headers':
                        if ($value) {
                            $headers[] = 'Access-Control-Allow-' . Strings::capitalize($key) . ': ' . $value;
                        }

                        break;

                    default:
                        throw new HttpException(tr('Unknown CORS header ":header" specified', [':header' => $key]));
                }
            }
        }

        // Add cache headers and store headers in object headers list
        return self::addCacheHeaders($headers);
    }



    /**
     * Send all the specified HTTP headers
     *
     * @note The amount of sent bytes does NOT include the bytes sent for the HTTP response code header
     * @param array|null $headers
     * @return int The amount of bytes sent. -1 if self::sendHeaders() was called for the second time.
     */
    public static function sendHttpHeaders(?array $headers): int
    {
        if (self::httpHeadersSent(true)) {
            // Headers already sent
            return -1;
        }

        if ($headers === null) {
            // Specified NULL for headers, which is what buildHeaders() returned, so there are no headers to send
            return -1;
        }

        try {
            $length = 0;

            // Set correct headers
            http_response_code(self::$http_code);

            if ((self::$http_code != 200)) {
                Log::warning(tr('Phoundation sent "HTTP:http" for URL ":url"', [
                    ':http' => (self::$http_code ? 'HTTP' . self::$http_code : 'HTTP0'),
                    ':url'  => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]));
            } else {
                Log::success(tr('Phoundation sent :http for URL ":url"', [
                    ':http' => (self::$http_code ? 'HTTP' . self::$http_code : 'HTTP0'),
                    ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]));
            }

            // Send all available headers
            foreach ($headers as $header) {
                $length += strlen($header);
                header($header);
            }

            return $length;

        } catch (Throwable $e) {
            Notification::new()
                ->setException($e)
                ->setTitle(tr('Failed to send headers to client'))
                ->send();

            // self::sendHeaders() itself crashed. Since self::sendHeaders() would send out http 500, and since it
            // crashed, it no longer can do this, send out the http 500 here.
            http_response_code(500);
            throw new $e;
        }
    }



    /**
     * Return HTTP caching headers
     *
     * Returns headers Cache-Control and ETag
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see htt_noCache()
     * @see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * @see https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     * @version 2.5.92: Added function and documentation

     * @param array $headers Any extra headers that are required
     * @return array
     */
    protected static function addCacheHeaders(array $headers): array
    {
        if (Config::get('web.cache.enabled', 'auto') === 'auto') {
            // PHP will take care of the cache headers

        } elseif (Config::get('web.cache.enabled', 'auto') === true) {
            // Place headers using phoundation algorithms
            if (!Config::get('web.cache.enabled', 'auto') or (self::$http_code != 200)) {
                // Non HTTP 200 / 304 pages should NOT have cache enabled! For example 404, 503 etc...
                $headers[] = 'Cache-Control: no-store, max-age=0';
                self::$etag = null;

            } else {
                // Send caching headers. Ajax, API, and admin calls do not have proxy caching
                switch (Core::getCallType()) {
                    case 'api':
                        // no-break
                    case 'ajax':
                        // no-break
                    case 'admin':
                        break;

                    default:
                        // Session pages for specific users should not be stored on proxy servers either
                        if (!empty($_SESSION['user']['id'])) {
                            Config::get('web.cache.cacheability', 'private');
                        }

                        $headers[] = 'Cache-Control: ' . Config::get('web.cache.cacheability', 'private') . ', ' . Config::get('web.cache.expiration', 'max-age=604800') . ', ' . Config::get('web.cache.revalidation', 'must-revalidate') . Config::get('web.cache.other', 'no-transform');

                        if (!empty(self::$etag)) {
                            $headers[] = 'ETag: "' . self::$etag . '"';
                        }
                }
            }
        }

        return $headers;
    }



    /**
     * Send the required headers to ensure that the page will not be cached ever
     *
     * @return void
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see Http::cache()
     * @version 2.5.92: Added function and documentation
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     */
    protected static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
        header('Cache-Control: post-check=0, pre-check=0', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 10 Jan 2000 07:00:00 GMT', true);
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheTest($etag = null): bool
    {
        self::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . $etag);

        if (!Config::get('web.cache.enabled', 'auto')) {
            return false;
        }

        if (Core::getCallType('ajax') or Core::getCallType('api')) {
            return false;
        }

        if ((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == self::$etag) {
            if (empty($core->register['flash'])) {
                /*
                 * The client sent an etag which is still valid, no body (or anything else) necesary
                 */
                http_headers(304, 0);
            }
        }

        return true;
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheEtag(): bool
    {
        // ETAG requires HTTP caching enabled. Ajax and API calls do not use ETAG
        if (!Config::get('web.cache.enabled', 'auto') or Core::getCallType('ajax') or Core::getCallType('api')) {
            self::$etag = null;
            return false;
        }

        // Create local ETAG
        self::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . Core::readRegister('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
        if (trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == self::$etag) {
            if (empty($core->register['flash'])) {
                // The client sent an etag which is still valid, no body (or anything else) necessary
                http_response_code(304);
                die();
            }
        }

        return true;
    }



    /**
     * Checks if HTTP headers have already been sent and logs warnings if so
     *
     * @param bool $send_now
     * @return bool
     */
    protected static function httpHeadersSent(bool $send_now = false): bool
    {
        if (headers_sent($file, $line)) {
            Log::warning(tr('Will not send HTTP headers again, output started at ":file@:line. Adding backtrace to debug this request', [
                ':file' => $file,
                ':line' => $line
            ]));
            Log::backtrace();
            return true;
        }

        if (self::$http_headers_sent) {
            // Since
            Log::warning(tr('HTTP Headers already sent by self::sendHeaders(). This can happen with PHP due to PHP ignoring output buffer flushes, causing this to be called over and over. just ignore this message.'), 2);
            return true;
        }

        if ($send_now) {
            self::$http_headers_sent = true;
        }

        return false;
    }
}