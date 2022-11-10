<?php

namespace Phoundation\Web;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Exception\PageException;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;
use Throwable;



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
class Page
{
    /**
     * Singleton
     *
     * @var Page|null $instance
     */
    protected static ?Page $instance = null;

    /**
     * The template class that build the UI
     *
     * @var Template|null $template
     */
    protected static ?Template $template = null;

    /**
     * The flash object for this user
     *
     * @var Flash|null
     */
    protected static ?Flash $flash = null;

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
    protected array $header_files = [];

    /**
     * The files that should be added in the footer
     *
     * @var array
     */
    protected array $footer_files = [];

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
     * Page class constructor
     */
    protected function __construct()
    {
        // Set the page hash
        self::$hash = sha1($_SERVER['REQUEST_URI']);

        try {
            $class = Config::get('web.template.class', 'test');

            if (!ctype_alnum($class)) {
                throw new PageException(tr('Configured page template ":class" is invalid; it should contain only letters and numbers', [
                    ':class' => $class
                ]));
            }

            $class = '\\Templates\\' . $class;

            include(Debug::getClassFile($class));
            self::$template = new $class($this);

            if (!(self::$template instanceof Template)) {
                throw new PageException(tr('Configured page template ":class" is invalid. The class should be implementing the interface Phoundation\Web\Template', [
                    ':class' => $class
                ]));
            }

            self::$headers['meta']['charset']  = Config::get('languages.encoding.charset', 'UTF-8');
            self::$headers['meta']['viewport'] = Config::get('web.viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no');

        } catch (ConfigNotExistsException $e) {
            throw new PageException(tr('No template specified, please ensure your configuration file contains "web.template.class"'), previous: $e);
        } catch (FilesystemException $e) {
            /*
             * Issue loading the class file.
             *
             * Possible issues:
             *
             * No file could be determined for the specified class
             * The file for the specified class is not readable
             * The file for the specified class is not PHP
             * The file for the specified class does not contain the specified class
             */
            throw new PageException(tr('Specified template class file could not be loaded because ":message"', [
                ':message' => $e->getMessage()
            ]));
        } catch (Exception $e) {
            if ($e->getMessage()) {
                throw $e;
            }

            // The configured template does not exist
            throw new PageException(tr('Invalid template specified, please check that the configuration "web.template" has a valid and existing template'));
        }
    }



    /**
     * Singleton, ensure to always return the same Page object.
     *
     * @return Page
     */
    public static function getInstance(): Page
    {
        if (!isset(self::$instance)) {
            self::$instance = new Page();
        }

        return self::$instance;
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
     * Returns the current tab index and automatically increments it
     *
     * @param string $doctype
     * @return Page
     */
    public static function setDoctype(string $doctype): Page
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
     * @return Page
     */
    public static function setTitle(string $title, bool $no_translate = false): Page
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
     * @return Page
     */
    public static function setCharset(?string $charset): Page
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
     * @return Page
     */
    public static function setViewport(?string $viewport): Page
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
     * @param string $target                  The target file that should be executed or sent to the client
     * @param boolean $attachment             If specified as true, will send the file as a downloadable attachement,
     *                                        to be written to disk instead of displayed on the browser. If set to
     *                                        false, the file will be sent as a file to be displayed in the browser
     *                                        itself.
     * @param Restrictions|null $restrictions If specified, apply the specified file system restrictions, which may
     *                                        block the request if the requested file is outside these restrictions
     * @return void
     * @throws Throwable
     * @package Web
     * @see route()
     * @note: This function will kill the process once it has finished executing / sending the target file to the client
     * @version 2.5.88: Added function and documentation
     */
    #[NoReturn] public static function execute(string $target, bool $attachment = false, ?Restrictions $restrictions = null): void
    {
        try {
            self::getInstance();

            Core::writeRegister($target, 'system', 'script_file');
            ob_start();

            switch (Core::getCallType()) {
                case 'ajax':
throw new UnderConstructionException();
                    $include = PATH_ROOT . 'www/' . $language . '/ajax/' . $page . '.php';

                    // Execute ajax page
                    Log::notice(tr('Showing ":language" language ajax page ":page"', [':page' => $page, ':language' => $language]));
                    include($include);

                case 'api':
throw new UnderConstructionException();
                    $include = PATH_ROOT . 'www/api/' . (is_numeric($page) ? 'system/' : '') . $page . '.php';

                    // Execute ajax page
                    Log::notice(tr('Showing ":language" language api page ":page"', [':page' => $page, ':language' => $language]));
                    include($include);

                case 'admin':
throw new UnderConstructionException();
                    $admin = '/admin';
                // no-break

                default:
                    // This is a normal web page
                    self::executeWebPage($target, $attachment, $restrictions);
            }

            // Send the page to the client
            Page::send();

        } catch (Exception $e) {
            Notification::new()
                ->setTitle(tr('Failed to execute page ":page"', [':page' => $target]))
                ->setException($e)
                ->send(false);

            throw $e;
        }

        die();
    }



    /**
     * Place the specified data directly into the output buffer
     *
     * @param string $data
     * @return int The length of the output buffer
     */
    public static function buffer(string $data): int
    {
        echo $data;
        return ob_get_length();
    }



    /**
     * Add the specified HTML to the output buffer
     *
     * @param string $html
     * @return void
     */
    public static function addHtml(string $html): void
    {
        echo $html;
    }



    /**
     * Access to the page template class
     *
     * @return Template
     */
    public static function template(): Template
    {
        return self::$template;
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
     * @return void
     */
    public static function send(): void
    {
        $body = '';

        /// Get all output buffers
        while(ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build HTML and minify the output
        self::$html = self::$template->buildHtmlHeader();
        self::$html_headers_sent = true;

        self::$html .= self::$template->buildPageHeader();
        self::$html .= $body;
        self::$html .= self::$template->buildPageFooter();
        self::$html .= self::$template->buildHtmlFooter();
        self::$html  = Html::minify(self::$html);

        // Send headers
        $length = self::$template->buildHttpHeaders();

        Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            // HEAD request, do not send any HTML whatsoever
            return;
        }

        switch (Http::getHttpCode()) {
            case 304:
                // 304 requests indicate the browser to use it's local cache, send nothing
                // no-break

            case 429:
                // 429 Tell the client that it made too many requests, send nothing
                return;
        }

        // Write to cache and output
        Cache::writePage(self::$hash, self::$html);

        $length = strlen(self::$html);

        // Send HTML to the client
        echo self::$html;
        ob_flush();
        flush();

        Log::success(tr('Sent ":length" bytes of HTML to client', [':length' => $length]), 4);
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
     * @return Page
     */
    public static function setFavIcon(string $url): Page
    {
        self::$headers['link'][$url] = [
            'rel'  => 'icon',
            'href' => Url::build($url)->cdn('js'),
            'type' => File::new()->mimetype($url)
        ];

        return self::getInstance();
    }



    /**
     * Load the specified javascript file(s)
     *
     * @param string|array $urls
     * @param bool|null $header
     * @return Page
     */
    public static function loadJavascript(string|array $urls, ?bool $header = null): Page
    {
        if ($header === null) {
            $header = Config::get('web.javascript.delay', true);
        }

        if ($header and self::$html_headers_sent) {
            Log::warning(tr('Not adding files ":files" to HTML headers as the HTML headers have already been generated', [
                ':files' => $urls
            ]));
        }

        foreach (Arrays::force($urls, '') as $url) {
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
     * @return Page
     */
    public static function loadCss(string|array $urls): Page
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
        <html lang="' . Session::getLanguage() . '">';

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
     * Execute a standard web page
     *
     * @param string $target
     * @param bool $attachment
     * @param Restrictions|null $restrictions
     * @return void
     */
    protected static function executeWebPage(string $target, bool $attachment = false, ?Restrictions $restrictions = null): void
    {
        if (Strings::fromReverse(dirname($target), '/') === 'system') {
            // Wait a small random time to avoid timing attacks on system pages
            usleep(mt_rand(1, 500));
        }

        // Find the correct target page
        $target = Filesystem::absolute(Strings::unslash($target), PATH_WWW . LANGUAGE);

        if (str_ends_with($target, 'php')) {
            if ($attachment) {
                // TODO Test this! Implement required HTTP headers!
                // Execute the PHP file and then send the output to the client as an attachment
                Log::action(tr('Executing file ":target" and sending output as attachment', [':target' => $target]));

                include($target);

                Http::file(new Restrictions(PATH_WWW, false, 'Page dynamic attachment'))
                    ->setAttachment(true)
                    ->setData(ob_get_clean())
                    ->setFilename(basename($target))
                    ->send();

            } else {
                // Execute the file and send the output HTML as a web page
                Log::action(tr('Executing page ":target" and sending output as HTML web page', [
                    ':target' => Strings::from($target, PATH_ROOT)
                ]));

                include($target);
            }

        } else {
            if ($attachment) {
                // TODO Test this! Implement required HTTP headers!
                // Upload the file to the client as an attachment
                Log::action(tr('Sending file ":target" as attachment', [':target' => $target]));

                Http::file(new Restrictions(PATH_WWW . ',data/attachments', false, 'Page attachment'))
                    ->setAttachment(true)
                    ->setFile($target)
                    ->setFilename(basename($target))
                    ->send();

            } else {
                // TODO Test this! Implement required HTTP headers!
                // Send the file directly
                $mimetype = mime_content_type($target);
                $bytes    = filesize($target);

                Log::action(tr('Sending contents of file ":target" with mime-type ":type" directly to client', [
                    ':target' => $target,
                    ':type' => $mimetype
                ]));

                header('Content-Type: ' . $mimetype);
                header('Content-length: ' . $bytes);

                include($target);
            }
        }
    }
}