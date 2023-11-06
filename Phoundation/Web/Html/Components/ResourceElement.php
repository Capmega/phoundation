<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Input\Interfaces\InputInterface;
use Phoundation\Web\Html\Components\Input\Traits\InputElement;
use Phoundation\Web\Html\Components\Interfaces\ResourceElementInterface;
use Phoundation\Web\Html\Exception\HtmlException;


/**
 * Class RenderElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ResourceElement extends Element implements ResourceElementInterface, InputInterface
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
     * @var string|null $empty
     */
    protected ?string $empty = null;

    /**
     * The text displayed when the specified resource is empty
     *
     * @var bool $hide_empty
     */
    protected bool $hide_empty = false;

    /**
     * The source array
     *
     * @var IteratorInterface|null $source
     */
    protected ?IteratorInterface $source = null;

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
     * If true, query source data will be stored in array source, so that it can be re-used
     *
     * @var bool $cache
     */
    protected bool $cache = false;


    /**
     * ResourceElement class constructor
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Set the HTML none element attribute
     *
     * @param string|null $none
     * @return static
     */
    public function setNone(?string $none): static
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
     * Returns the HTML empty element attribute
     *
     * @return string|null
     */
    public function getEmpty(): ?string
    {
        return $this->empty;
    }


    /**
     * Sets the HTML empty element attribute
     *
     * @param string|null $empty
     * @return static
     */
    public function setObjectEmpty(?string $empty): static
    {
        $this->empty = $empty;
        return $this;
    }


    /**
     * Returns whether query sources will be cached or not
     *
     * @return bool
     */
    public function getCache(): bool
    {
        return $this->cache;
    }


    /**
     * Sets whether query sources will be cached or not
     *
     * @param bool $cache
     * @return static
     */
    public function setCache(bool $cache): static
    {
        $this->cache = $cache;
        return $this;
    }


    /**
     * Sets if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @param bool $hide_empty
     * @return static
     */
    public function setHideEmpty(bool $hide_empty): static
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
     * Returns the array source
     *
     * @return IteratorInterface|null
     */
    public function getSource(): ?IteratorInterface
    {
        return $this->source;
    }


    /**
     * Sets the array source
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|string|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source, array|string|null $execute = null): static
    {
        if ($this->source_query) {
            throw new HtmlException(tr('Cannot specify source, a source query was already specified'));
        }

        $this->source = Iterator::new()->setSource($source);
        return $this;
    }


    /**
     * Returns the array source
     *
     * @return PDOStatement|null
     */
    public function getSourceQuery(): ?PDOStatement
    {
        return $this->source_query;
    }


    /**
     * Sets a query source
     *
     * @param PDOStatement|string|null $source_query
     * @param array|string|null $execute
     * @return $this
     */
    public function setSourceQuery(PDOStatement|string|null $source_query, array|string|null $execute = null): static
    {
        if ($this->source) {
            throw new HtmlException(tr('Cannot specify source query, a source was already specified'));
        }

        if (is_string($source_query)) {
            // Get a PDOStatement instead by executing the query
            $source_query = sql()->query($source_query, $execute);
        }

        $this->source_query = $source_query;
        return $this;
     }


    /**
     * Sets the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @param array $source_data
     * @return static
     */
    public function setSourceData(array $source_data): static
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
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string
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
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function buildAttributes(): IteratorInterface
    {
        return parent::buildAttributes();
    }


    /**
     * Generates and returns the HTML body
     *
     * @return string|null
     */
    abstract public function renderBody(): ?string;
}
