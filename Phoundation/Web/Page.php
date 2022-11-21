<?php

namespace Phoundation\Web;

use Exception;
use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Notifications\Notification;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\Html\Template\TemplatePage;
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
     * @var Page $instance
     */
    protected static Page $instance;

    /**
     * The server filesystem restrictions
     *
     * @var Server $server
     */
    protected static Server $server;

    /**
     * The template class that builds the UI
     *
     * @var TemplatePage $template_page
     */
    protected static TemplatePage $template_page;

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
     * Page class constructor
     *
     * @throws Exception
     */
    protected function __construct()
    {
        // Set the page hash
        self::$hash = sha1($_SERVER['REQUEST_URI']);

        self::$headers['meta']['charset']  = ['charset'  => Config::get('languages.encoding.charset', 'UTF-8')];
        self::$headers['meta']['viewport'] = ['viewport' => Config::get('web.viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no')];
    }



    /**
     * Singleton
     *
     * @return static
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
     * @return Server
     */
    public static function getServer(): Server
    {
        return self::$server;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param Server $server
     * @return static
     */
    public static function setServer(Server $server): static
    {
        self::$server = $server;
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
     * @param string $target      The target file that should be executed or sent to the client
     * @param boolean $attachment If specified as true, will send the file as a downloadable attachement, to be written
     *                            to disk instead of displayed on the browser. If set to false, the file will be sent as
     *                            a file to be displayed in the browser itself.
     * @return string|null
     * @throws Throwable
     * @package Web
     * @see route()
     * @note: This function will kill the process once it has finished executing / sending the target file to the client
     * @version 2.5.88: Added function and documentation
     */
    public static function execute(string $target, ?Template $template = null, bool $attachment = false): ?string
    {
        try {
            if (Strings::fromReverse(dirname($target), '/') === 'system') {
                // Wait a small random time to avoid timing attacks on system pages
                usleep(mt_rand(1, 500));
            }

            // Do we have access to this page?
            self::$server->checkRestrictions($target, false);

            // Do we have a cached version available?
            $output = Cache::read($target);

            if ($output) {
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

                Core::writeRegister($target, 'system', 'script_file');
                ob_start();

                Log::notice(tr('Executing ":type" page ":page" with language ":language"', [
                    ':type'     => Core::getCallType(),
                    ':page'     => $target,
                    ':language' => LANGUAGE
                ]));

                $output = match (Core::getCallType()) {
                    'ajax', 'api' => self::$api_interfaqce->execute($target),
                    default       => self::$template_page->execute($target),
                };

                // Write output to cache
                Cache::write(self::$hash, $output);
            }

            // Send it directly as an output
            if ($attachment) {
                return $output;
            }

            // Send the page to the client
            Page::send($output);

        } catch (Exception $e) {
            Notification::new()
                ->setTitle(tr('Failed to execute ":type" page ":page" with language ":language"', [
                    ':type'     => Core::getCallType(),
                    ':page'     => $target,
                    ':language' => LANGUAGE
                ]))
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
     * @return TemplatePage
     */
    public static function template(): TemplatePage
    {
        return self::$template_page;
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
}