<?php

namespace Phoundation\Web;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cache\Cache;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Web\Exception\RouteException;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;



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
     * Information that goes into the HTML header
     *
     * @var array $headers
     */
    protected static array $headers = [];

    /**
     * Information that goes into the HTML footer
     *
     * @var array $footers
     */
    protected static array $footers = [];

    /**
     * The HTML buffer for this page
     *
     * @var string $html
     */
    protected static string $html = '';



//    /**
//     * Javascript files that will be loaded starting at the footer of the page
//     *
//     * @var array $footer_scripts
//     */
//    protected array $footer_scripts = [];



    /**
     * Process the routed target
     *
     * We have a target for the requested route. If the resource is a PHP page, then
     * execute it. Anything else, send it directly to the client
     *
     * @param string $target             The target file that should be executed or sent to the client
     * @param boolean $attachment        If specified as true, will send the file as a downloadable attachement, to be
     *                                   written to disk instead of displayed on the browser. If set to false, the file
     *                                   will be sent as a file to be displayed in the browser itself.
     * @param Restrictions $restrictions If specified, apply the specified file system restrictions, which may block the
     *                                   request if the requested file is outside these restrictions
     * @return void
     * @throws \Throwable
     * @package Web
     * @see route()
     * @note: This function will kill the process once it has finished executing / sending the target file to the client
     * @version 2.5.88: Added function and documentation
     */
    #[NoReturn] public static function execute(string $target, bool $attachment, Restrictions $restrictions): void
    {
        if (str_ends_with($target, 'php')) {
            if ($attachment) {
                throw new RouteException(tr('Found "A" flag for executable target ":target", but this flag can only be used for non PHP files', [
                    ':target' => $target
                ]));
            }

            Log::action(tr('Executing page ":target"', [':target' => $target]));

            include($target);

        } else {
            if ($attachment) {
                // Upload the file to the client as an attachment
                $target = File::absolutePath(Strings::unslash($target), PATH_ROOT.'www/');

                Log::action(tr('Sending file ":target" as attachment', [':target' => $target]));

                Http::file($restrictions)
                    ->setAttachment(true)
                    ->setFile($target)
                    ->setFilename(basename($target))
                    ->send();

            } else {
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
     * Returns the HTML output buffer for this page
     *
     * @return string
     */
    public static function getHtml(): string
    {
        return self::$html;
    }



    /**
     * Returns the length HTML output buffer for this page
     *
     * @return int
     */
    public static function getContentLength(): int
    {
        return strlen(self::$html);
    }



    /**
     * Send the current buffer to the client
     *
     * @return void
     */
    public static function send(): void
    {

        self::$html  = Html::buildHeaders();
        self::$html .= self::buildHeaders();
        self::$html .= self::buildFooters();
        self::$html .= Html::buildFooters();

        // Minify the output
        self::$html = Html::minify(self::$html);

        Http::sendHeaders();

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

        Cache::writePage(self::$html, self::$unique_key);

        // Send HTML to the client
        echo self::$html;
    }



    /**
     * Build and return the page headers
     *
     * @return string
     */
    protected static function buildHeaders(): string
    {

    }



    /**
     * Build and return the page footers
     *
     * @return string
     */
    protected static function buildFooters(): string
    {

    }
}