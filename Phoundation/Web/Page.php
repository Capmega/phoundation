<?php

namespace Phoundation\Web;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Notifications\Notification;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Template\Template;
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
     * Current page singleton
     *
     * @var Page|null $current
     */
    protected static ?Page $current = null;

    /**
     * The template class that builds the UI
     *
     * @var Template|null $template
     */
    protected ?Template $template;

    /**
     * The server filesystem restrictions
     *
     * @var Server $server
     */
    protected Server $server;

    /**
     * The flash object for this user
     *
     * @var Flash|null
     */
    protected ?Flash $flash = null;

    /**
     * !DOCTYPE variable
     *
     * @var string
     */
    protected string $doctype = 'html';

    /**
     * The page title
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * Information that goes into the HTML header
     *
     * @var array $headers
     */
    protected array $headers = [
        'link'       => [],
        'meta'       => [],
        'javascript' => []
    ];

    /**
     * Information that goes into the HTML footer
     *
     * @var array $footers
     */
    protected array $footers = [
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
    protected string $html = '';

    /**
     * The unique hash for this page
     *
     * @var string|null $hash
     */
    protected ?string $hash = null;

    /**
     * Keeps track on if the HTML headers have been sent / generated or not
     *
     * @var bool $html_headers_sent
     */
    protected bool $html_headers_sent = false;



    /**
     * Page class constructor
     *
     * @param Template $template
     * @param Server $server
     * @throws Exception
     */
    protected function __construct(Template $template, Server $server)
    {
        // Set the page hash
        $this->hash = sha1($_SERVER['REQUEST_URI']);

        $this->server   = $server;
        $this->template = $template;

        $this->headers['meta']['charset']  = ['charset'  => Config::get('languages.encoding.charset', 'UTF-8')];
        $this->headers['meta']['viewport'] = ['viewport' => Config::get('web.viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no')];
    }



    /**
     * Returns a new page object
     *
     * @return static
     */
    public static function new(Template $template, Server $server): static
    {
        self::$current = new static($template, $server);
        return self::$current;
    }



    /**
     * Returns the current page object
     *
     * @return static
     */
    public static function current(): static
    {
        return self::$current;
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return string
     */
    public function getDocType(): string
    {
        return $this->doctype;
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @param string $doctype
     * @return Page
     */
    public function setDoctype(string $doctype): static
    {
        $this->doctype = $doctype;
        return $this;
    }



    /**
     * Returns the page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }



    /**
     * Sets the page title
     *
     * @param string $title
     * @param bool $no_translate
     * @return Page
     */
    public function setTitle(string $title, bool $no_translate = false): static
    {
        $this->title = $title;
        return $this;
    }



    /**
     * Returns the page charset
     *
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return isset_get($this->headers['meta']['charset']);
    }



    /**
     * Sets the page charset
     *
     * @param string|null $charset
     * @return Page
     */
    public function setCharset(?string $charset): static
    {
        $this->headers['meta']['charset'] = $charset;
        return $this;
    }



    /**
     * Returns the page viewport
     *
     * @return string|null
     */
    public function getViewport(): ?string
    {
        return isset_get($this->headers['meta']['viewport']);
    }



    /**
     * Sets the page viewport
     *
     * @param string|null $viewport
     * @return Page
     */
    public function setViewport(?string $viewport): static
    {
        $this->headers['meta']['viewport'] = $viewport;
        return $this;
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
     * @param Server|array|string|null $server If specified, apply the specified file system restrictions, which may
     *                                        block the request if the requested file is outside these restrictions
     * @return void
     * @throws Throwable
     * @package Web
     * @see route()
     * @note: This function will kill the process once it has finished executing / sending the target file to the client
     * @version 2.5.88: Added function and documentation
     */
    #[NoReturn] public function execute(string $target, bool $attachment = false, Server|array|string|null $server = null): void
    {
        try {
            if (Strings::fromReverse(dirname($target), '/') === 'system') {
                // Wait a small random time to avoid timing attacks on system pages
                usleep(mt_rand(1, 500));
            }

            Core::writeRegister($target, 'system', 'script_file');
            ob_start();

            Log::notice(tr('Executing ":type" page ":page" with language ":language"', [
                ':type'     => Core::getCallType(),
                ':page'     => $target,
                ':language' => LANGUAGE
            ]));

            switch (Core::getCallType()) {
                case 'ajax':
                    // no-break

                case 'api':
                    include($target);
                    break;

                case 'admin':
                    // no-break
                default:
                    // This is a normal web page
                    $this->template->execute($target);
            }

            // Send the page to the client
            Page::send();

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
    public function buffer(string $data): int
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
    public function addHtml(string $html): void
    {
        echo $html;
    }



    /**
     * Access to the page template class
     *
     * @return TemplatePage
     */
    public function template(): TemplatePage
    {
        return $this->template;
    }



    /**
     * Returns the HTML output buffer for this page
     *
     * @return string
     */
    public function getHtml(): string
    {
        return ob_get_contents();
    }



    /**
     * Returns the HTML unique hash
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }



    /**
     * Returns if the HTML headers have been sent
     *
     * @return bool
     */
    public function getHtmlHeadersSent(): bool
    {
        return $this->html_headers_sent;
    }



    /**
     * Returns the length HTML output buffer for this page
     *
     * @return int
     */
    public function getContentLength(): int
    {
        return ob_get_length();
    }



    /**
     * Send the current buffer to the client
     *
     * @return void
     */
    public function send(): void
    {
        $body = '';

        /// Get all output buffers
        while(ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build HTML and minify the output
        $this->html = $this->template->buildHtmlHeader();
        $this->html_headers_sent = true;

        $this->html .= $this->template->buildPageHeader();
        $this->html .= $this->template->buildMenu();
        $this->html .= $body;
        $this->html .= $this->template->buildPageFooter();
        $this->html .= $this->template->buildHtmlFooter();
        $this->html  = Html::minify($this->html);

        // Send headers
        $length = $this->template->buildHttpHeaders();

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
        Cache::writePage($this->hash, $this->html);

        $length = strlen($this->html);

        // Send HTML to the client
        echo $this->html;
        ob_flush();
        flush();

        Log::success(tr('Sent ":length" bytes of HTML to client', [':length' => $length]), 4);
    }



    /**
     * Access the Flash object
     *
     * @return Flash
     */
    public function flash(): Flash
    {
        if (!$this->flash) {
            $this->flash = new Flash();
        }

        return $this->flash;
    }



    /**
     * Add meta information
     *
     * @param array $meta
     * @return void
     */
    public function addMeta(array $meta): void
    {
        $this->headers['meta'][] = $meta;
    }



    /**
     * Set the favicon for this page
     *
     * @param string $url
     * @return Page
     */
    public function setFavIcon(string $url): static
    {
        try {
            $this->headers['link'][$url] = [
                'rel'  => 'icon',
                'href' => Url::build($url)->img(),
                'type' => File::new(Filesystem::absolute($url, 'img'), PATH_CDN . LANGUAGE . '/img')->mimetype()
            ];
        } catch (FilesystemException $e) {
            Log::warning($e->makeWarning());
        }

        return $this;
    }



    /**
     * Load the specified javascript file(s)
     *
     * @param string|array $urls
     * @param bool|null $header
     * @return Page
     */
    public function loadJavascript(string|array $urls, ?bool $header = null): static
    {
        if ($header === null) {
            $header = Config::get('web.javascript.delay', true);
        }

        if ($header and $this->html_headers_sent) {
            Log::warning(tr('Not adding files ":files" to HTML headers as the HTML headers have already been generated', [
                ':files' => $urls
            ]));
        }

        foreach (Arrays::force($urls, '') as $url) {
            if ($header) {
                $this->headers['javascript'][$url] = [
                    'type' => 'text/javascript',
                    'src'  => Url::build($url)->js()
                ];

            } else {
                $this->footers['javascript'][$url] = [
                    'type' => 'text/javascript',
                    'src'  => Url::build($url)->js()
                ];
            }
        }

        return $this;
    }



    /**
     * Load the specified CSS file(s)
     *
     * @param string|array $urls
     * @return Page
     */
    public function loadCss(string|array $urls): static
    {
        foreach (Arrays::force($urls, '') as $url) {
            $this->headers['link'][$url] = [
                'rel'  => 'stylesheet',
                'href' => Url::build($url)->css(),
            ];
        }

        return $this;
    }



    /**
     * Build and return the HTML headers
     *
     * @return string|null
     */
    public function buildHeaders(): ?string
    {
        $return = '<!DOCTYPE ' . $this->doctype . '>
        <html lang="' . Session::getLanguage() . '">' . PHP_EOL;

        if ($this->title) {
            $return .= '<title>' . $this->title . '</title>' . PHP_EOL;
        }

        foreach ($this->headers['meta'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<meta ' . $header . ' />' . PHP_EOL;
        }

        foreach ($this->headers['link'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<link ' . $header . ' />' . PHP_EOL;
        }

        foreach ($this->headers['javascript'] as $header) {
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
    public function buildFooters(): ?string
    {
        $return = '';

        foreach ($this->footers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"');
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return;
    }
}