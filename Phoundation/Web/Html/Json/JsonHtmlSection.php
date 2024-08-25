<?php

/**
 * Class JsonHtmlSections
 *
 * This class represents a single JSON HTML section to be returned in an API or AJAX reply
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Json;

use Phoundation\Web\Exception\WebRenderException;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Json\Enums\EnumJsonHtmlMethods;
use Phoundation\Web\Html\Json\Interfaces\JsonHtmlSectionInterface;


class JsonHtmlSection implements JsonHtmlSectionInterface
{
    /**
     * Tracks the jQuery selector for the client
     *
     * @var string $selector
     */
    protected string $selector;

    /**
     * Tracks the method to be executed on the client.
     *
     * @var EnumJsonHtmlMethods $method
     */
    protected EnumJsonHtmlMethods $method;

    /**
     * Tracks the HTML for this section
     *
     * @var string|null $html
     */
    protected ?string $html;



    /**
     * JsonHtmlSection class constructor
     *
     * @param string|null $selector
     */
    public function __construct(?string $selector = null)
    {
        $this->setSelector($selector);
    }


    /**
     * Returns a new JsonHtmlSection object
     *
     * @param string|null $selector
     *
     * @return $this
     */
    public static function new(?string $selector = null): static
    {
        return new static($selector);
    }


    /**
     * Returns the client jQuery selector
     *
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }


    /**
     * Sets the client jQuery selector
     *
     * @param string $selector
     *
     * @return static
     */
    public function setSelector(string $selector): static
    {
        $this->selector = $selector;
        return $this;
    }


    /**
     * Returns the client HTML processing method
     *
     * @return EnumJsonHtmlMethods
     */
    public function getMethod(): EnumJsonHtmlMethods
    {
        return $this->method;
    }


    /**
     * Sets the client HTML processing method
     *
     * @param EnumJsonHtmlMethods $method
     *
     * @return static
     */
    public function setMethod(EnumJsonHtmlMethods $method): static
    {
        $this->method = $method;
        return $this;
    }


    /**
     * Returns the HTML for this section
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }


    /**
     * Sets the HTML for this section
     *
     * @param RenderInterface|string $html
     *
     * @return static
     */
    public function setHtml(RenderInterface|string $html): static
    {
        if ($html instanceof RenderInterface) {
            // Auto render to HTML
            $html = $html->render();
        }

        $this->html = $html;
        return $this;
    }


    /**
     * Renders the JSON data array
     *
     * @return array
     */
    public function renderJson(): array
    {
        if (empty($this->selector)) {
            throw new WebRenderException(tr('Cannot render JsonHtmlSection, no selector was specified'));
        }

        if (empty($this->method)) {
            throw new WebRenderException(tr('Cannot render JsonHtmlSection with selector ":selector", no selector was specified', [
                ':selector' => $this->selector
            ]));
        }

        return [
            'selector' => $this->getSelector(),
            'method'   => $this->getMethod(),
            'html'     => $this->getHtml(),
        ];
    }
}
