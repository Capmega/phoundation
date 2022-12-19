<?php

namespace Phoundation\Web\Http\Html\Elements;

use PDOStatement;
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
     * @var int|null $hide_empty
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
     * @var PDOStatement|null $source_query
     */
    protected ?PDOStatement $source_query = null;

    /**
     * The source for "data-*" attributes where the data key matches the source key
     *
     * @var array $source_data
     */
    protected array $source_data = [];

    /**
     * The amount of entries added to this element from the source data (query or array)
     *
     * @var int $count
     */
    protected int $count = 0;


    /**
     * Set the HTML none element attribute
     *
     * @param string|null $none
     * @return static
     */
    public function setNone(?string $none): self
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
     * Sets the HTML empty element attribute
     *
     * @param string|null $empty
     * @return static
     */
    public function setEmpty(?string $empty): self
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
     * Sets if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @param bool $hide_empty
     * @return static
     */
    public function setHideEmpty(bool $hide_empty): self
    {
        $this->hide_empty = $hide_empty;
        return $this;
    }



    /**
     * Returns if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @return bool
     */
    public function getHideEmpty(): bool
    {
        return $this->hide_empty;
    }



    /**
     * Set the HTML source element attribute
     *
     * @param mixed $source
     * @return static
     */
    public function setSourceArray(mixed $source): self
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
     * @param PDOStatement|string|null $source_query
     * @return static
     */
    public function setSourceQuery(PDOStatement|string|null $source_query): self
    {
        if ($this->source) {
            throw new HtmlException(tr('Cannot specify source query, a source was already specified'));
        }

        if (is_string($source_query)) {
            // Get a PDOStatement instead by executing the query
            $source_query = sql()->query($source_query);
        }

        $this->source_query = $source_query;
        return $this;
    }



    /**
     * Returns the HTML source_query element attribute
     *
     * @return PDOStatement|null
     */
    public function getSourceQuery(): ?PDOStatement
    {
        return $this->source_query;
    }



    /**
     * Sets the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @param array $source_data
     * @return static
     */
    public function setSourceData(array $source_data): self
    {
        $this->source_data = $source_data;
        return $this;
    }



    /**
     * Returns the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @return array
     */
    public function getSourceData(): array
    {
        return $this->source_data;
    }



    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string
     */
    public function render(): string
    {
        // Render the body
        $this->content = $this->renderBody();

        if (!$this->content and $this->hide_empty) {
            return '';
        }

        // Render the top element around the resource block
        return parent::render();
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, tabindex, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        return array_merge(parent::buildAttributes(), []);
    }



    /**
     * Generates and returns the HTML body
     *
     * @return string
     */
    abstract public function renderBody(): string;
}