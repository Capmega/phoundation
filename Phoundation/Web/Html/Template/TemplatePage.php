<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Page;


/**
 * Template class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class TemplatePage
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @param string $target
     * @param bool $main_content_only
     * @return string|null
     */
    public function execute(string $target, bool $main_content_only = false): ?string
    {
        $body = $this->buildBody($target, $main_content_only);

        if ($main_content_only) {
            return $body;
        }

        // Build HTML and minify the output
        $output = $this->buildHtmlHeader();
        Page::htmlHeadersSent(true);

        if (Page::getBuildBody()) {
            // TODO With hooks, this should be executed in Core startup instead and this replaced by a hook
            Plugins::start();

            $output .= $this->buildPageHeader();
            $output .= $this->buildMenu();
            $output .= $body;
            $output .= $this->buildPageFooter();
        } else {
            // Page requested that no body parts be built
            $output .= $body;
        }

        $output .= $this->buildHtmlFooter();
        $output  = Html::minify($output);

        // Build Template specific HTTP headers
        $this->buildHttpHeaders($output);
        return $output;
    }


    /**
     * Build the page body
     *
     * @param string $target
     * @param bool $main_content_only
     * @return string|null
     */
    public function buildBody(string $target, bool $main_content_only = false): ?string
    {
        return execute_page($target);
    }


    /**
     * Build and send HTTP headers
     *
     * @param string $output
     * @return void
     */
    abstract public function buildHttpHeaders(string $output): void;

    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    abstract public function buildHtmlHeader(): ?string;

    /**
     * Build the page header
     *
     * @return string|null
     */
    abstract public function buildPageHeader(): ?string;

    /**
     * Build the page menu
     *
     * @return string|null
     */
    abstract public function buildMenu(): ?string;

    /**
     * Build the page footer
     *
     * @return string|null
     */
    abstract public function buildPageFooter(): ?string;
}
