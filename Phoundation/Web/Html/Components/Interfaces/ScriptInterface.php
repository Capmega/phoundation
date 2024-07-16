<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Web\Html\Enums\EnumAttachJavascript;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;

interface ScriptInterface
{
    /**
     * Returns if this script is loaded async
     *
     * @return bool
     */
    public function getAsync(): bool;


    /**
     * Sets if this script is loaded async
     *
     * @param bool $async
     *
     * @return static
     */
    public function setAsync(bool $async): static;


    /**
     * Returns if this script is loaded from a file instead of included internally
     *
     * @return bool
     */
    public function getToFile(): bool;


    /**
     * Sets if this script is loaded from a file instead of included internally
     *
     * @param bool $to_file
     *
     * @return static
     */
    public function setToFile(bool $to_file): static;


    /**
     * Returns the script src
     *
     * @return string
     */
    public function getSrc(): string;


    /**
     * Sets the script src
     *
     * @param string $src
     *
     * @return static
     */
    public function setSrc(string $src): static;


    /**
     * Returns where this script is attached to the document
     *
     * @return EnumAttachJavascript
     */
    public function getAttach(): EnumAttachJavascript;


    /**
     * Sets where this script is attached to the document
     *
     * @param EnumAttachJavascript $attach
     *
     * @return static
     */
    public function setAttach(EnumAttachJavascript $attach): static;


    /**
     * Returns if this script is loaded defer
     *
     * @return bool
     */
    public function getDefer(): bool;


    /**
     * Sets if this script is loaded defer
     *
     * @param bool $defer
     *
     * @return static
     */
    public function setDefer(bool $defer): static;


    /**
     * Returns the event wrapper code for this script
     *
     * @return EnumJavascriptWrappers
     */
    public function getJavascriptWrapper(): EnumJavascriptWrappers;


    /**
     * Sets the event wrapper code for this script
     *
     * @param EnumJavascriptWrappers $javascript_wrapper
     *
     * @return static
     */
    public function setJavascriptWrapper(EnumJavascriptWrappers $javascript_wrapper): static;


    /**
     * Generates and returns the HTML string for a <script> element
     *
     * @note: If web.javascript.delay is configured true, it will return an empty string and add the string to the
     *        footer script tags list instead so that it will be loaded at the end of the page for speed
     * @return string|null
     */
    public function render(): ?string;
}