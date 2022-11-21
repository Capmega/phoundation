<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Core\Log;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Page;



/**
 * Template class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * The target page to execute
     *
     * @var string $target
     */
    protected string $target;



    /**
     * TemplatePage constructor
     */
    public function __construct()
    {
    }



    /**
     * Returns a new TargetPage object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        $body = include($target);

        // Get all output buffers and restart buffer
        while(ob_get_level()) {
            $body .= ob_get_contents();
            ob_end_clean();
        }

        ob_start(chunk_size: 4096);

        // Build HTML and minify the output
        $output = $this->buildHtmlHeader();
        Page::htmlHeadersSent(true);

        $output .= $this->buildPageHeader();
        $output .= $this->buildMenu();
        $output .= $body;
        $output .= $this->buildPageFooter();
        $output .= $this->buildHtmlFooter();
        $output  = Html::minify($output);

        // Build headers
        $this->buildHttpHeaders();

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            // HEAD request, do not send any HTML whatsoever
            return null;
        }

        return $output;
    }



    /**
     * Build and send HTTP headers
     *
     * @return void
     */
    public abstract function buildHttpHeaders(): void;

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public abstract function buildHtmlHeader(): ?string;

    /**
     * Build the page header
     *
     * @return string|null
     */
    public abstract function buildPageHeader(): ?string;

    /**
     * Build the page menu
     *
     * @return string|null
     */
    public abstract function buildMenu(): ?string;

    /**
     * Build the page footer
     *
     * @return string|null
     */
    public abstract function buildPageFooter(): ?string;
}