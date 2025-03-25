<?php

/**
 * Class ResourceElement
 *
 * This class is an abstract HTML element object class that can display resource data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Cache\Cache;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataCacheKey;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataDataIterator;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataIterator;
use Phoundation\Data\Traits\TraitMethodEnsureArrayString;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Input\Interfaces\SelectedInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\ValueInterface;
use Phoundation\Web\Html\Components\Interfaces\ResourceElementInterface;
use Phoundation\Web\Html\Exception\HtmlException;
use Phoundation\Web\Html\Traits\TraitInputElement;
use Phoundation\Web\Requests\Request;


abstract class ResourceElementCore extends ElementCore implements ResourceElementInterface
{
    use TraitDataCacheKey;
    use TraitInputElement;
    use TraitDataConnector;
    use TraitDataIterator;
    use TraitDataDebug;
    use TraitMethodEnsureArrayString {
        setSource as protected __setSource;
    }


    /**
     * The text displayed for "none selected"
     *
     * @var string|null $not_selected_label
     */
    protected ?string $not_selected_label = null;

    /**
     * The text displayed when the specified resource is empty
     *
     * @var string|null $component_empty_label
     */
    protected ?string $component_empty_label = null;

    /**
     * The text displayed when the specified resource is empty
     *
     * @var bool $hide_empty
     */
    protected bool $hide_empty = false;

    /**
     * The query that will generate the source data
     *
     * @var PDOStatement|null $source_query
     */
    protected ?PDOStatement $source_query = null;

    /**
     * The columns to use, in case the query returns more columns than should be used
     *
     * @var array|null $use_columns
     */
    protected ?array $use_columns = null;

    /**
     * The source for "data-*" attributes where the data key matches the source key
     *
     * @var array $data_source
     */
    protected array $data_source = [];

    /**
     * The number of entries added to this element from the source data (query or array)
     *
     * @var int $count
     */
    protected int $count = 0;

    /**
     * If true, query source data will be stored in the array source, so that it can be re-used
     *
     * @var bool $cache
     */
    protected bool $cache = false;


    /**
     * Table constructor
     *
     * @param IteratorInterface|array|null $source
     */
    public function __construct(IteratorInterface|array|null $source = null)
    {
        parent::__construct();

        $this->cache = Cache::isEnabled();

        if ($source instanceof IteratorInterface) {
            $this->setIteratorObject($source);
        }
    }


    /**
     * Returns the contents of this iterator object as a JSON string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->render();
    }


    /**
     * Returns the HTML none element attribute
     *
     * @return string|null
     */
    public function getNotSelectedLabel(): ?string
    {
        return $this->not_selected_label;
    }


    /**
     * Set the HTML none element attribute
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setNotSelectedLabel(?string $label): static
    {
        $this->not_selected_label = $label;
        return $this;
    }


    /**
     * Returns the HTML empty element attribute
     *
     * @return string|null
     */
    public function getComponentEmptyLabel(): ?string
    {
        return $this->component_empty_label;
    }


    /**
     * Sets the HTML empty element attribute
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setComponentEmptyLabel(?string $label): static
    {
        $this->component_empty_label = $label;
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
     *
     * @return static
     */
    public function setCache(bool $cache): static
    {
        $this->cache = $cache;
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
     * Sets if this element will be hidden (Element::render() will return an empty string) if the resource is empty
     *
     * @param bool $hide_empty
     *
     * @return static
     */
    public function setHideEmpty(bool $hide_empty): static
    {
        $this->hide_empty = $hide_empty;
        return $this;
    }


    /**
     *  Sets the source data for this object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     * @param bool                                             $filter_meta
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static
    {
        if ($this->source_query) {
            throw new HtmlException(tr('Cannot specify source, a source query was already specified'));
        }

        $this->__setSource($source, $execute)
             ->ensureArrayStrings();

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
     * @param array|string|null        $execute
     * @param array|null               $use_columns
     *
     * @return static
     */
    public function setSourceQuery(PDOStatement|string|null $source_query, array|string|null $execute = null, ?array $use_columns = null): static
    {
        if ($source_query and $this->source) {
            throw new HtmlException(tr('Cannot specify source query ":query", this ":class" object already contains source data', [
                ':query' => $source_query,
                ':class' => static::class,
            ]));
        }

        if (is_string($source_query)) {
            // Get a PDOStatement instead by executing the query
            $source_query = sql($this->o_connector)->setDebug($this->debug)
                                                   ->query($source_query, $execute);
        }

        $this->source_query = $source_query;
        $this->use_columns  = $use_columns;

        return $this;
    }


    /**
     * Returns the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     * @return array
     */
    public function getDataSource(): array
    {
        return $this->data_source;
    }


    /**
     * Sets the source for "data-*" attributes where the data key matches the source key
     *
     * @note The format should be as follows: [id => [key => value, key => value], id => [...] ...] This format will
     *       then add the specified keys to each option where the value matches the id
     *
     * @param array $data_source
     *
     * @return static
     */
    public function setDataSource(array $data_source): static
    {
        $this->data_source = $data_source;
        return $this;
    }


    /**
     * Generates and returns a unique cache key for this DataEntry object
     *
     * @param String|null $append_string
     *
     * @return string|null
     */
    public function getCacheKeySeed(?String $append_string = null): ?string
    {
        if ($this->o_iterator) {
            // Get cache key from DataEntry object
            return 'ResourceElement-' . $this->o_iterator->getCacheKeySeed('-render') . $append_string;
        }

        if ($this instanceof SelectedInterface) {
            // This resource element contains a single or multiple selected value
            return 'ResourceElement-' . static::class . '-' . $this->getId() . '-' . $this->getName() . '-' . Json::encode($this->getSelected()) . '-render' . $append_string;
        }

        if ($this instanceof ValueInterface) {
            // This resource element contains a single value
            return 'ResourceElement-' . static::class . '-' . $this->getId() . '-' . $this->getName() . '-' . $this->getValue() . '-render' . $append_string;
        }

        // This object can't be cached
        return null;
    }


    /**
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return cache('html')->get($this->getCacheKey(), function () {
            // Render the body
            $this->content = $this->renderBody();

            if (!$this->content and $this->hide_empty) {
                return '';
            }

            // Render the top element around the resource block
            return parent::render();
        });
    }


    /**
     * Generates and returns the HTML body
     *
     * @return string|null
     */
    abstract public function renderBody(): ?string;


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, tabindex, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function renderAttributesArray(): IteratorInterface
    {
        return parent::renderAttributesArray();
    }
}
