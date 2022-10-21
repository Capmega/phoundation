<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Http\Html\Exception\HtmlException;



/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Element
{
    /**
     * The HTML id element attribute
     *
     * @var string|null $id
     */
    protected ?string $id = null;

    /**
     * The HTML name element attribute
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The HTML tabindex element attribute
     *
     * @var int|null $tabindex
     */
    protected ?int $tabindex = null;

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
     * HtmlObject constructor
     */
    public function __construct()
    {
        $this->tabindex = Html::getTabIndex();
    }



    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @return Element
     */
    public function setId(?string $id): Element
    {
        $this->id = $id;
        return $this;
    }



    /**
     * Returns the HTML id element attribute
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }



    /**
     * Sets the HTML name element attribute
     *
     * @param string|null $name
     * @return Element
     */
    public function setName(?string $name): Element
    {
        $this->name = $name;
        return $this;
    }



    /**
     * Returns the HTML name element attribute
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



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
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return Element
     */
    public function setTabIndex(?int $tabindex): Element
    {
        $this->tabindex = $tabindex;
        return $this;
    }


    /**
     * Returns the HTML tabindex element attribute
     *
     * @return int|null
     */
    public function getTabIndex(): ?int
    {
        return $this->tabindex;
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
     * Generates and returns the HTML string
     *
     * @return string
     */
    public abstract function render(): string;
}