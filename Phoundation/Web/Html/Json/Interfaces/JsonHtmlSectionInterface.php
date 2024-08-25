<?php

namespace Phoundation\Web\Html\Json\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\RenderJsonInterface;
use Phoundation\Web\Html\Json\Enums\EnumJsonHtmlMethods;


interface JsonHtmlSectionInterface extends RenderJsonInterface
{
    /**
     * Returns the client jQuery selector
     *
     * @return string
     */
    public function getSelector(): string;


    /**
     * Sets the client jQuery selector
     *
     * @param string $selector
     *
     * @return static
     */
    public function setSelector(string $selector): static;


    /**
     * Returns the client HTML processing method
     *
     * @return EnumJsonHtmlMethods
     */
    public function getMethod(): EnumJsonHtmlMethods;


    /**
     * Sets the client HTML processing method
     *
     * @param EnumJsonHtmlMethods $method
     *
     * @return static
     */
    public function setMethod(EnumJsonHtmlMethods $method): static;


    /**
     * Returns the HTML for this section
     *
     * @return string
     */
    public function getHtml(): string;


    /**
     * Sets the HTML for this section
     *
     * @param string $html
     *
     * @return static
     */
    public function setHtml(string $html): static;
}
