<?php

namespace Phoundation\Web\Http\Html;

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
     * @return Element
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
     * @return Element
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
     * @return Element
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
     * @return Element
     */
    public function setSource(mixed $source): self
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
     * @return Element
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
     * @return Element
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
        $body = $this->renderBody();

        if (!$body and $this->hide_empty) {
            return '';
        }

        // Render header and return
        return $this->renderHeaders() . $body . $this->renderFooters();
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
        if ($this->auto_submit) {
            $this->addClass('auto_submit');
            // TODO Add auto submit script to the script loader, also check possibly relevant autosubmit lines below
//            // Autosubmit on the specified selector
//            $params['autosubmit'] = str_replace('[', '\\\\[', $params['autosubmit']);
//            $params['autosubmit'] = str_replace(']', '\\\\]', $params['autosubmit']);
//            return $return.Html::script('$("[name=\''.$params['autosubmit'].'\']").change(function() { $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');
        }

// TODO Implement autosubmit
//        'on_change'  => ($this->on_change ? Elements::jQuery('$("#' . $this->id . '").change(function() { '.$this->on_change . ' });')->render() : null),

        return array_merge(parent::buildAttributes(), []);
    }



    /**
     * Generates and returns the HTML headers for this element
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        return parent::render();
    }



    /**
     * Generates and returns the HTML footers for this element
     *
     * @return string
     */
    protected function renderFooters(): string
    {
        return '</' . $this->element . '>';
    }



    /**
     * Generates and returns the HTML body
     *
     * @return string
     */
    public abstract function renderBody(): string;
}