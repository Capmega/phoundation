<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Web\Http\Html\Exception\HtmlException;



/**
 * Class RenderElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ResourceElement extends Element
{
    use InputElement;



    /**
     * The text displayed for "none selected"
     *
     * @var string|null $none
     */
    protected ?string $none = null;

    /**
     * The text displayed when the specified resource is empty
     *
     * @var int|null $empty
     */
    protected ?int $empty = null;

    /**
     * The text displayed when the specified resource is empty
     *
     * @var int|null $empty
     */
    protected ?int $hide_empty = null;

    /**
     * The source data
     *
     * @var mixed $source
     */
    protected mixed $source = null;

    /**
     * The query that will generate the source data
     *
     * @var string|null $source_query
     */
    protected ?string $source_query = null;



    /**
     * Set the HTML none element attribute
     *
     * @param string|null $none
     * @return Element
     */
    public function setNone(?string $none): Element
    {
        $this->none = $none;
        return $this;
    }



    /**
     * Returns the HTML none element attribute
     *
     * @return string|null
     */
    public function getNone(): ?string
    {
        return $this->none;
    }



    /**
     * Set the HTML empty element attribute
     *
     * @param string|null $empty
     * @return Element
     */
    public function setEmpty(?string $empty): Element
    {
        $this->empty = $empty;
        return $this;
    }



    /**
     * Returns the HTML empty element attribute
     *
     * @return string|null
     */
    public function getEmpty(): ?string
    {
        return $this->empty;
    }



    /**
     * Set the HTML source element attribute
     *
     * @param mixed $source
     * @return Element
     */
    public function setSource(mixed $source): Element
    {
        if ($this->source) {
            throw new HtmlException(tr('Cannot specify source, a source query was already specified'));
        }

        $this->source = $source;
        return $this;
    }



    /**
     * Returns the HTML source element attribute
     *
     * @return mixed
     */
    public function getSource(): mixed
    {
        return $this->source;
    }



    /**
     * Set the HTML source_query element attribute
     *
     * @param string|null $source_query
     * @return Element
     */
    public function setSourceQuery(?string $source_query): Element
    {
        if ($this->source) {
            throw new HtmlException(tr('Cannot specify source query, a source was already specified'));
        }

        $this->source_query = $source_query;
        return $this;
    }



    /**
     * Returns the HTML source_query element attribute
     *
     * @return string|null
     */
    public function getSourceQuery(): ?string
    {
        return $this->source_query;
    }



    /**
     * Generates and returns the HTML headers
     *
     * @return string
     */
    public function render(): string
    {
        return self::renderHeaders() . self::renderBody();
    }



    /**
     * Generates and returns the HTML headers
     *
     * @return string
     */
    protected abstract function renderHeaders(): string;



    /**
     * Generates and returns the HTML body
     *
     * @return string
     */
    public abstract function renderBody(): string;
}