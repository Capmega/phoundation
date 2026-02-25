<?php

/**
 * Class HtmlTable
 *
 * This class can create various HTML tables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Tables;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataCellCallbacks;
use Phoundation\Data\Traits\TraitDataColumns;
use Phoundation\Data\Traits\TraitDataRowCallbacks;
use Phoundation\Data\Traits\TraitDataTitle;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\RegexException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Input\InputCheckbox;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\ResourceElement;
use Phoundation\Web\Html\Components\Tables\Exception\TablesException;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Traits\TraitObjectButtons;
use Phoundation\Web\Html\Traits\TraitObjectTopButtons;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Stringable;
use Throwable;

class HtmlTable extends ResourceElement implements HtmlTableInterface
{
    use TraitObjectButtons;
    use TraitObjectTopButtons;
    use TraitDataColumns {
        setColumns as protected __setColumns;
    }
    use TraitDataTitle;
    use TraitDataRowCallbacks;
    use TraitDataCellCallbacks;


    /**
     * The HTML class element attribute cache for the <tr> element
     *
     * @var string|null $row_classes
     */
    protected ?string $row_classes = null;

    /**
     * The status name for NULL status
     *
     * @var string|null $null_status
     */
    protected ?string $null_status = null;

    /**
     * The HTML class element attribute cache for the <td> element
     *
     * @var string|null $column_classes
     */
    protected ?string $column_classes = null;

    /**
     * The HTML class element attribute cache for the <a> element in row columns
     *
     * @var string|null $anchor_classes
     */
    protected ?string $anchor_classes = null;

    /**
     * Contains a list of URLs that apply to some or all cells from a row
     */
    protected ?array $row_urls = null;

    /**
     * Contains a list of URLs that apply to some or all cells
     *
     * @var array|null $column_urls
     */
    protected ?array $column_urls = null;

    /**
     * queries that apply to all rows
     *
     * @var array|null $row_queries
     */
    protected ?array $row_queries = null;

    /**
     * The table column headers
     *
     * @var IteratorInterface|null $headers
     */
    protected ?IteratorInterface $headers = null;

    /**
     * The table column footers
     *
     * @var array|null $footers
     */
    protected ?array $footers = null;

    /**
     * Data attributes for anchors
     *
     * @var IteratorInterface|null $anchor_data_attributes
     */
    protected ?IteratorInterface $anchor_data_attributes = null;

    /**
     * Convert columns to checkboxes, buttons, etc
     *
     * @var IteratorInterface|null $convert_columns
     */
    protected ?IteratorInterface $convert_columns = null;

    /**
     * Sets how the id columns will be displayed
     *
     * @var EnumTableIdColumn $checkbox_selectors
     */
    protected EnumTableIdColumn $checkbox_selectors = EnumTableIdColumn::hidden;

    /**
     * Sets whether the table is responsive or not
     *
     * @var bool $responsive
     */
    protected bool $responsive = true;

    /**
     * Sets whether the table is full width or not
     *
     * @var bool $full_width
     */
    protected bool $full_width = true;

    /**
     * Table header text
     *
     * @var Stringable|string|null $header_text
     */
    protected Stringable|string|null $header_text = null;

    /**
     * If true, will process all cell contents with htmlentities()
     *
     * @var bool $process_entities
     */
    protected bool $process_entities = true;

    /**
     * The object where this table was generated from
     *
     * @var IteratorInterface|null $from
     */
    protected ?IteratorInterface $from = null;

    /**
     * Cache of HtmlTable::$columns that has the columns inverted to do more rapid hash lookups
     *
     * @var array|null $column_cache
     */
    protected ?array $column_cache = null;

    /**
     * Tracks a list of cached Anchors
     *
     * @var array $anchors
     */
    protected array $anchors = [];

    /**
     * Tracks what content should be rendered if the cell is empty
     *
     * @var string $empty_cell
     */
    protected string $empty_cell = '-';


    /**
     * Table constructor
     *
     * @param IteratorInterface|array|null $source
     */
    public function __construct(IteratorInterface|array|null $source = null)
    {
        parent::__construct();

        $this->setElement('table')
             ->setNullStatus(tr('Active'))
             ->setDataIteratorObject($source);
    }


    /**
     * Sets the columns
     *
     * @param ArrayableInterface|array|string|null $columns
     *
     * @return static
     */
    public function setColumns(ArrayableInterface|array|string|null $columns): static
    {
        if ($columns) {
            $this->column_cache = array_flip($columns);
            $this->column_cache = Arrays::setValues($this->column_cache, true);

        } else {
            $this->column_cache = null;
        }

        return $this->__setColumns($columns);
    }


    /**
     * Sets the DataIterator object
     *
     * @param IteratorInterface|null $_iterator
     *
     * @return static
     */
    public function setDataIteratorObject(?IteratorInterface $_iterator): static
    {
        if ($_iterator) {
            $this->setComponentEmptyLabel(tr('No :types available', [
                ':types' => $_iterator->getIteratorName(),
            ]));

        } else {
            $this->setComponentEmptyLabel(tr('No results available'));
        }

        return parent::setIteratorObject($_iterator);
    }


    /**
     * Returns if the table is header_text or not
     *
     * @return Stringable|string|null
     */
    public function getHeaderText(): Stringable|string|null
    {
        return $this->header_text;
    }


    /**
     * Sets if the table is header_text or not
     *
     * @param Stringable|string|null $header_text
     *
     * @return static
     */
    public function setHeaderText(Stringable|string|null $header_text): static
    {
        $this->header_text = $header_text;
        return $this;
    }


    /**
     * Returns what content should be rendered if the cell is empty
     *
     * @return string|null
     */
    public function getEmptyCell(): ?string
    {
        return $this->empty_cell;
    }


    /**
     * Sets what content should be rendered if the cell is empty
     *
     * @param string|null $empty_cell
     *
     * @return static
     */
    public function setEmptyCell(?string $empty_cell): static
    {
        $this->empty_cell = $empty_cell;
        return $this;
    }


    /**
     * Returns the label for status column NULL values
     *
     * @return string|null
     */
    public function getNullStatus(): ?string
    {
        return $this->null_status;
    }


    /**
     * Sets the label for status column NULL values
     *
     * @param string|null $null_status
     *
     * @return static
     */
    public function setNullStatus(?string $null_status): static
    {
        $this->null_status = $null_status;
        return $this;
    }


    /**
     * Returns if the table is responsive or not
     *
     * @return bool
     */
    public function getResponsive(): bool
    {
        return $this->responsive;
    }


    /**
     * Sets if the table is responsive or not
     *
     * @param bool $responsive
     *
     * @return static
     */
    public function setResponsive(bool $responsive): static
    {
        $this->responsive = $responsive;
        return $this;
    }


    /**
     * Sets if the table processes entities in the source data or not
     *
     * @return bool
     */
    public function getProcessEntities(): bool
    {
        return $this->process_entities;
    }


    /**
     * Sets if the table processes entities in the source data or not
     *
     * @param bool $process_entities
     *
     * @return static
     */
    public function setProcessEntities(bool $process_entities): static
    {
        $this->process_entities = $process_entities;
        return $this;
    }


    /**
     * Returns if the table is full width or not
     *
     * @return bool
     */
    public function getFullWidth(): bool
    {
        return $this->full_width;
    }


    /**
     * Sets if the table is full width or not
     *
     * @param bool $full_width
     *
     * @return static
     */
    public function setFullWidth(bool $full_width): static
    {
        $this->full_width = $full_width;
        return $this;
    }


    /**
     * Returns the column's data attributes
     *
     * @return IteratorInterface
     */
    public function getAnchorDataAttributes(): IteratorInterface
    {
        if (empty($this->anchor_data_attributes)) {
            $this->anchor_data_attributes = new Iterator();
        }

        return $this->anchor_data_attributes;
    }


    /**
     * Returns the classes used for <tr> tags
     *
     * @return string|null
     */
    public function getRowClasses(): ?string
    {
        return $this->row_classes;
    }


    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $classes
     *
     * @return static
     */
    public function setRowClasses(?string $classes): static
    {
        $this->row_classes = $classes;
        return $this;
    }


    /**
     * Returns the HTML class element attribute for <td> tags
     *
     * @return string|null
     */
    public function getColumnClasses(): ?string
    {
        return $this->column_classes;
    }


    /**
     * Sets the HTML class element attribute for <td> tags
     *
     * @param string|null $classes
     *
     * @return static
     */
    public function setColumnClasses(?string $classes): static
    {
        $this->column_classes = $classes;
        return $this;
    }


    /**
     * Returns the HTML class element attribute for <td> tags
     *
     * @return string|null
     */
    public function getAnchorClasses(): ?string
    {
        return $this->anchor_classes;
    }


    /**
     * Sets the HTML class element attribute for <td> tags
     *
     * @param string|null $classes
     *
     * @return static
     */
    public function setAnchorClasses(?string $classes): static
    {
        $this->anchor_classes = $classes;
        return $this;
    }


    /**
     * Returns if the first column automatically is converted to checkboxes
     *
     * @return EnumTableIdColumn
     */
    public function getCheckboxSelectors(): EnumTableIdColumn
    {
        return $this->checkbox_selectors;
    }


    /**
     * Sets if the first column automatically is converted to checkboxes
     *
     * @param EnumTableIdColumn $checkbox_selectors
     *
     * @return static
     */
    public function setCheckboxSelectors(EnumTableIdColumn $checkbox_selectors): static
    {
        $this->checkbox_selectors = $checkbox_selectors;
        return $this;
    }


    /**
     * Returns the URL that applies to each row
     *
     * @return array
     */
    public function getRowUrls(): array
    {
        if ($this->row_urls === null) {
            $this->row_urls = [];
        }

        return $this->row_urls;
    }


    /**
     * Sets the URL that applies to each row
     *
     * @param UrlInterface|string|null $_url
     * @param array|null               $restrictions
     * @param int                      $priority
     *
     * @return static
     */
    public function setRowUrls(UrlInterface|string|null $_url, ?array $restrictions = null, int $priority = 0): static
    {
        $this->row_urls = [];
        return $this->addRowUrl($_url, $restrictions, $priority);
    }


    /**
     * Sets the URL that applies to each row
     *
     * @param UrlInterface|string|null $_url
     * @param array|null               $restrictions
     * @param int                      $priority
     *
     * @return static
     */
    public function addRowUrl(UrlInterface|string|null $_url, ?array $restrictions = null, int $priority = 0): static
    {
        if ($_url) {
        $this->getRowUrls();

        $this->row_urls[] = [
            'url'          => Url::new($_url),
            'restrictions' => $restrictions,
            'priority'     => $priority
        ];
        }

        return $this;
    }


    /**
     * Returns the URL that applies to each row
     *
     * @return array|null
     */
    public function getRowQueries(): ?array
    {
        return $this->row_queries;
    }


    /**
     * Sets the URL that applies to each row
     *
     * @param Stringable|array|null $row_queries
     *
     * @return static
     */
    public function setRowQueries(Stringable|array|null $row_queries): static
    {
        $this->row_queries = $row_queries;

        return $this;
    }


    /**
     * Returns the table headers
     *
     * @return array
     */
    public function getFooters(): array
    {
        if (empty($this->footers)) {
            $this->footers = [];
        }

        return $this->footers;
    }


    /**
     * Returns the table headers
     *
     * @param IteratorInterface|array|null $footers
     *
     * @return static
     */
    public function setFooters(IteratorInterface|array|null $footers): static
    {
        if ($footers instanceof IteratorInterface) {
            $footers = $footers->getSource();
        }

        $this->footers = $footers;
        return $this;
    }


    public function render(): ?string
    {
        $attributes = [];
        $id         = $this->getId();
        $name       = $this->getName();

        if ($id) {
            $attributes[] = 'id="' . $id . '_table"';
        }

        if ($name) {
            $attributes[] = 'name="' . $name . '_table"';
        }

        if ($attributes) {
            $attributes = ' ' . implode(' ', $attributes);

        } else {
            $attributes = null;
        }

        return '<div' . $attributes . '>' . $this->header_text . parent::render() . '</div>';
    }


    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string
    {
        $return  = $this->renderTopButtons();
        $return .= $this->renderBodyQuery();
        $return .= $this->renderBodyArray();

        if (!$return) {
            return $this->renderBodyEmpty();
        }

        return $this->renderHeaders() . $return . $this->renderFooters();
    }


    /**
     * Renders the table top buttons, if defined
     *
     * @return string|null
     */
    protected function renderTopButtons(): ?string
    {
        if (empty($this->_top_buttons)) {
            return null;
        }

        return $this->_top_buttons->addClass('top-buttons')->render();
    }


    /**
     * Generates and returns the HTML string for a table body from an array source
     *
     * This will return all HTML FROM the <tbody> tags around it
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\Phoundation\AdminLte\Html\Components\Tables\TemplateHtmlTable::render()
     * @see \Templates\Phoundation\AdminLte\Html\Components\Tables\TemplateHtmlTable::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    protected function renderBodyQuery(): ?string
    {
        $return = '<tbody>';

        if (!$this->source_query) {
            return null;
        }

        if (!$this->source_query->rowCount()) {
            return '';
        }

        // Process SQL resource
        while ($row = $this->source_query->fetch()) {
            $this->executeRowCallbacks($row, EnumTableRowType::row, $params);

            if (isset($this->columns)) {
                $row = Arrays::keepKeysOrdered($row, $this->columns);
            }

            $return .= $this->doRenderRow(array_value_first($row), $row, $params);
        }

        return $return . '</tbody>';
    }


    /**
     * Returns the requested row
     *
     * @param int  $row
     * @param bool $exception
     *
     * @return array|null
     */
    public function getRow(int $row, bool $exception = false): ?array
    {
        return array_get_safe($this->source, $row, exception: $exception);
    }


    /**
     * Renders and returns the current single table row
     *
     * @param int $row_id
     *
     * @return string
     */
    public function renderRow(int $row_id): string
    {
        $row = $this->getRow($row_id);
        $this->executeRowCallbacks($row, EnumTableRowType::row, $params);
        return $this->doRenderRow($row_id, $row, []);
    }


    /**
     * Renders and returns a single table row
     *
     * @param string|float|int|null $row_id
     * @param array                 $row_values
     * @param array                 $params
     *
     * @return string|null
     */
    protected function doRenderRow(string|float|int|null $row_id, array $row_values, array $params): ?string
    {
        $this->ensureHeadersAndColumns($row_values);

        // ID is the first value in the row
        $this->count++;

        $cells = null;
        $row   = '<tr' . $this->renderRowClassString($params) . '>';
        $first = true;

        foreach ($this->columns as $column) {
            $value         = array_get_safe($row_values, $column);
            $made_checkbox = false;

            if ($first) {
                // Convert first column to checkboxes?
                $value = $this->renderCheckboxColumn($column, $value, $made_checkbox);
                $first = false;

                $params['htmlentities'] = !($this->process_entities or $made_checkbox);
                $params['no_url']       = (array_get_safe($params, 'no_url', false) or $made_checkbox or !$value);

                // If HtmlTable::renderCheckboxColumn() returned NULL, it means that we should not render this cell
                if ($value !== null) {
                    $this->executeCellCallbacks($row_id, $column, $value, $row_values, $params);
                    $cells .= $this->renderCell($row_id, $column, $value, $row_values, $params);
                }

            } else {
                $params['no_url'] = false;

                $this->executeCellCallbacks($row_id, $column, $value, $row_values, $params);

                $cells .= $this->renderCell($row_id, $column, $value, $row_values, $params);
            }
        }

        if ($cells) {
            return $row . $cells . '</tr>';
        }

        if (Debug::isEnabled()) {
            throw new TablesException(tr('Row data ":data" rendered an empty table row', [
                ':data' => $row_values,
            ]));
        }

        // We have rendered an empty row, return nothing
        Log::warning(ts('Row data ":data" rendered an empty table row', [
            ':data' => $row_values,
        ]));

        return null;
    }


    /**
     * Returns the table headers
     *
     * @return IteratorInterface
     */
    public function getHeaders(): IteratorInterface
    {
        if (empty($this->headers)) {
            $this->headers = Iterator::new()->setExceptionOnGet(false);
        }

        return $this->headers;
    }


    /**
     * Sets the table headers
     *
     * @param IteratorInterface|array|null $headers
     *
     * @return HtmlTable
     */
    public function setHeaders(IteratorInterface|array|null $headers): static
    {
        if ($headers) {
            if (is_array($headers)) {
                $this->headers = new Iterator($headers);
                $this->setColumns(array_keys($headers));

            } else {
                $this->headers = $headers;
                $this->setColumns(array_keys($headers->getSource()));
            }

        } else {
            $this->headers = null;
            $this->setColumns(null);
        }

        return $this;
    }


    /**
     * Builds and returns the class string
     *
     * @param array $params
     *
     * @return string|null
     */
    protected function renderRowClassString(array $params): ?string
    {
        $classes = null;

        if ($this->row_classes) {
            $classes .= ' ' . $this->row_classes;
        }

        if (array_get_safe($params, 'row_classes')) {
            $classes .= ' ' . $params['row_classes'];
        }

        return ' class="' . $classes . '"';
    }


    /**
     * Changes the first column to a checkbox
     *
     * @param string|int            $column
     * @param string|float|int|null $value
     * @param bool                  $made_checkbox
     *
     * @return string|null
     */
    protected function renderCheckboxColumn(string|int $column, string|float|int|null $value, bool &$made_checkbox): string|null
    {
        switch ($this->checkbox_selectors) {
            case EnumTableIdColumn::hidden:
                return null;

            case EnumTableIdColumn::visible:
                return (string) $value;

            case EnumTableIdColumn::checkbox:
                // no break

            default:
                $made_checkbox = true;

                return InputCheckbox::new()
                                    ->setName($column . '[]')
                                    ->setValue($value)
                                    ->render();
        }
    }


    /**
     * Returns a table cell
     *
     * @param string|float|int|null                 $row_id
     * @param string|float|int|null                 $column
     * @param Stringable|string|float|int|bool|null $value
     * @param array                                 $row_values
     * @param array                                 $params
     *
     * @return string|null
     */
    protected function renderCell(string|float|int|null $row_id, string|float|int|null $column, Stringable|string|float|int|bool|null $value, array $row_values, array $params): ?string
    {
        if (!$this->renderColumn($column)) {
            return null;
        }

        if (($column === 'status') and $value === null) {
            // Default status label for when status is NULL
            $value = $this->null_status;
        }

        $value = $value ?: $this->empty_cell;

        // Use row or column URL's?
        // Use column convert?
        // Add data-* in this option?
        $attributes  = '';
        $value       = (string) $value;
        $convert     = $this->getConvertColumns()->get($column);
        $attributes .= $this->renderCellData($row_id, $column);

        if ($convert) {
            if (is_callable($convert)) {
                // Convert this column
                $converted = $convert($value);

                if (!is_string($converted)) {
                    throw new OutOfBoundsException(tr('Conversion for column ":column" callback does not return a string as required', [
                        ':column' => $column,
                    ]));
                }

                $value = $converted;
            }

        } else {
            if ($this->process_entities and $params['htmlentities'] and empty($params['skiphtmlentities'][$column])) {
                $value = htmlspecialchars($value);
                $value = str_replace(PHP_EOL, '<br>', $value);
            }
        }

        if (!is_empty($value)) {
            $queries = $this->getRowQueries();
            $value   = $this->renderAnchor($row_id, $column, $value, $row_values, $params, $queries);
        }

        // Build row with TD tags with attributes
        return '<td' . $attributes . $this->renderColumnClassString() . '>' . $value . '</td>';
    }


    /**
     * Returns the list of URL's that can apply to each column cell (when optionally matching the restrictions)
     *
     * @return array
     */
    public function getColumnUrls(): array
    {
        if (empty($this->column_urls)) {
            $this->column_urls = [];
        }

        return $this->column_urls;
    }


    /**
     * Sets the URL that applies to each column
     *
     * @param UrlInterface|string $_url
     * @param array|null          $restrictions
     * @param int                 $priority
     *
     * @return static
     */
    public function setColumnUrls(UrlInterface|string $_url, ?array $restrictions = null, int $priority = 0): static
    {
        $this->column_urls = [];
        return $this->addColumnUrl($_url, $restrictions, $priority);
    }


    /**
     * Adds a URL that apply to each column cell (when optionally matching the restrictions)
     *
     * @param UrlInterface|string $_url
     * @param array|null          $restrictions
     * @param int                 $priority
     *
     * @return static
     */
    public function addColumnUrl(UrlInterface|string $_url, ?array $restrictions = null, int $priority = 0): static
    {
        $this->getColumnUrls();

        $this->column_urls[] = [
            'url'          => $_url,
            'restrictions' => $restrictions,
            'priority'     => $priority,
        ];

        return $this;
    }


    /**
     * Returns the table's column conversions
     *
     * @return IteratorInterface
     */
    public function getConvertColumns(): IteratorInterface
    {
        if (empty($this->convert_columns)) {
            $this->convert_columns = Iterator::new()->setExceptionOnGet(false);
        }

        return $this->convert_columns;
    }


    /**
     * Renders the data attributes for this cell
     *
     * @param string|float|int|null $row_id
     * @param string                $column
     *
     * @return string|null
     */
    protected function renderCellData(string|float|int|null $row_id, string $column): ?string
    {
        if (array_key_exists($row_id, $this->data_source)) {
            $cell_data = [];

            if (array_key_exists('', $this->data_source[$row_id])) {
                foreach ($this->data_source[$row_id][''] as $key => $value) {
                    $cell_data[] = ' data-' . $key . '="' . $value . '"';
                }
            }

            if (array_key_exists($column, $this->data_source[$row_id])) {
                foreach ($this->data_source[$row_id][$column] as $key => $value) {
                    $cell_data[] = ' data-' . $key . '="' . $value . '"';
                }
            }

            if ($cell_data) {
                return implode(' ', $cell_data);
            }
        }

        return null;
    }


    /**
     * Determines what URL should be applied for the
     *
     * @param array $row_values
     * @param array $params
     *
     * @return UrlInterface|null
     */
    protected function getCellUrl(array $row_values, array $params): ?UrlInterface
    {
        if (array_get_safe($params, 'no_url')) {
            return null;
        }

        // First select matching column URL
        $column_url = $this->getCellUrlFromSource($this->getColumnUrls(), $row_values, $column_priority);
        $row_url    = $this->getCellUrlFromSource($this->getRowUrls()   , $row_values, $row_priority);

        if ($column_url) {
            // We have a column URL
            if ($row_url) {
                // We also have a row URL! The latter takes precedence if the priorities are the same
                if ($row_priority >= $column_priority) {
                    return $row_url;
                }
            }

            return $column_url;
        }

        return $row_url;
    }


    /**
     * Determines what URL should be applied for the
     *
     * @param array    $urls
     * @param array    $row_values
     * @param int|null $priority
     *
     * @return UrlInterface|null
     */
    protected function getCellUrlFromSource(array $urls, array $row_values, ?int &$priority = null): ?UrlInterface
    {
        foreach ($urls as $url) {
            if (array_get_safe($url, 'restrictions')) {
                foreach ($url['restrictions'] as $restriction_column => $restriction_value) {
                    $priority = array_get_safe($url, 'priority', 0);

                    if (array_key_exists($restriction_column, $row_values)) {
                        $source_value = $row_values[$restriction_column];

                        switch (substr($restriction_value, 0, 1)) {
                            case '<': // Column value must be lesser than
                                if ($source_value < $restriction_value) {
                                    return $url['url'];
                                }

                                break;

                            case '>':  // Column value must be larger than
                                if ($source_value > $restriction_value) {
                                    return $url['url'];
                                }

                                break;

                            case '*': // Column value must match by regular expression
                                try {
                                    if (preg_match($source_value, $restriction_value)) {
                                        return $url['url'];
                                    }

                                } catch (Throwable $e) {
                                    throw new RegexException(tr('Could not test HtmlTable column ":column" value ":value" with regex ":regex", the regular expression failed to apply which probably means it is invalid', [
                                        ':value'  => $value,
                                        ':regex'  => $restriction_value,
                                        ':column' => $column,
                                    ]), $e);
                                }

                                break;

                            case '!': // Column value must not match
                                if ($source_value !== $restriction_value) {
                                    return $url['url'];
                                }

                                break;

                            case '=': // Column value must match
                                // no break

                            default:
                                if ($source_value === $restriction_value) {
                                    return $url['url'];
                                }
                        }
                    }
                }

                return null;
            }

            return $url['url'];
        }

        // No URL matched
        return null;
    }


    /**
     * Builds a URL around the specified column value
     *
     * @param mixed      $row_id
     * @param mixed      $column
     * @param string     $value
     * @param array      $row_values
     * @param array      $params
     * @param array|null $queries
     *
     * @return string|null
     */
    protected function renderAnchor(mixed $row_id, mixed $column, string $value, array $row_values, array $params, ?array $queries): ?string
    {
        $_url = $this->getCellUrl($row_values, $params);

        if (empty($_url) or (array_get_safe($params, 'no_render_url'))) {
            return $value;
        }

        // Ensure all :ROW and :COLUMN markings are converted
        $url        = $_url->getSource();
        $anchor_url = $url;
        $anchor_url = str_replace(':ROW'     , urlencode((string) $row_id), $anchor_url);
        $anchor_url = str_replace('%3AROW'   , urlencode((string) $row_id), $anchor_url);
        $anchor_url = str_replace(':COLUMN'  , urlencode((string) $column), $anchor_url);
        $anchor_url = str_replace('%3ACOLUMN', urlencode((string) $column), $anchor_url);
        $attributes = '';

        if ($queries) {
            $_url->addQueries($queries);
        }

        if ($this->anchor_data_attributes) {
            foreach ($this->anchor_data_attributes as $data_key => $data_value) {
                $attributes .= ' data-' . $data_key . '="' . $data_value . '"';
            }
        }

        $_anchor = array_get_safe($this->anchors, $url);

        if (empty($_anchor)) {
            $_anchor = Anchor::new($_url)
                              ->setClass($this->renderAnchorClassString())
                              ->setExtraAttributes($attributes);

            if (!$_anchor->hasRequiredRights()) {
                return null;
            }

            $this->anchors[$url] = $_anchor;
        }

        return $_anchor->clearRenderCache() // TODO Remove this once setting Element attributes automatically clears the render cache!
                        ->setUrlObject($anchor_url, false)
                        ->setContent($value)
                        ->render();
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function renderAnchorClassString(): ?string
    {
        if ($this->anchor_classes) {
            return ' class="' . $this->anchor_classes . '"';
        }

        return null;
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function renderColumnClassString(): ?string
    {
        if ($this->column_classes) {
            return ' class="' . $this->column_classes . '"';
        }

        return null;
    }


    /**
     * Generates and returns the HTML string for a table body from an array source
     *
     * This will return all HTML FROM the <tbody> tags around it
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     * @see \Templates\Phoundation\AdminLte\Html\Components\Tables\TemplateHtmlTable::render()
     * @see \Templates\Phoundation\AdminLte\Html\Components\Tables\TemplateHtmlTable::renderHeaders()
     * @see ResourceElement::renderBody()
     * @see ElementInterface::render()
     */
    protected function renderBodyArray(): ?string
    {
        if (!$this->source) {
            return null;
        }

        $return = '<tbody>';

        // Process array resource. Go over each row and in each row over each column
        foreach ($this->source as $row_id => $row) {
            if (!is_array($row)) {
                if (!$row instanceof ArrayableInterface) {
                    throw new OutOfBoundsException(tr('The specified table source array is invalid. Format should be [[header columns][row columns][row columns] ...] or contain an object with ArreableInterface Interface. a ":type" was encountered instead', [
                        ':type' => gettype($row),
                    ]));
                }

                // Row values is actually an object, get its content
                $row = $row->__toArray();
            }

            $this->executeRowCallbacks($row, EnumTableRowType::row, $params);

            if (isset($this->columns)) {
                $row = Arrays::keepKeysOrdered($row, $this->columns);
            }

            $return .= $this->doRenderRow($row_id, $row, $params);
        }

        return $return . '</tbody>';
    }


    /**
     * Render an <option> for "this select has no data and is empty"
     *
     * @return string
     */
    protected function renderBodyEmpty(): string
    {
        // No content (other than maybe the "none available" entry) was added
        $return = '<tbody>';

        if ($this->component_empty_label) {
            $return .= '<tr class="empty-row"><td class="text-center">' . $this->component_empty_label . '</td></tr>';
        }

        return $return . '</tbody>';
    }


    /**
     * Render the table headers
     *
     * @return string|null
     */
    protected function renderHeaders(): ?string
    {
        if (!$this->headers) {
            // No headers because no content
            return null;
        }

        $return = '<thead><tr>';
        $first  = true;

        foreach ($this->columns as $column) {
            if (!$this->renderColumn($column)) {
                continue;
            }

            $header = $this->headers->get($column);

            if ($first) {
                $first = false;

                switch ($this->checkbox_selectors) {
                    case EnumTableIdColumn::hidden:
                        break;

                    case EnumTableIdColumn::checkbox:
                        $return .= '<th>' . InputCheckbox::new()
                                                         ->setName($column . '[]')
                                                         ->setValue(1)
                                                         ->render() . '
                                    </th>';
                        break;

                    case EnumTableIdColumn::visible:
                        $return .= '<th>' . $header . '</th>';
                        break;
                }

            } else {
                $return .= '<th>' . $header . '</th>';
            }
        }

        return $return . '</tr></thead>';
    }


    /**
     * Render the table footers
     *
     * @return string|null
     */
    protected function renderFooters(): ?string
    {
        if (!$this->footers) {
            // No footers because no content
            return null;
        }

        $first   = true;
        $return  = '<tfoot><tr>';

        $this->executeRowCallbacks($this->footers, EnumTableRowType::footer, $params);

        foreach ($this->columns as $column) {
            if (!$this->renderColumn($column)) {
                continue;
            }

            $footer = array_get_safe($this->footers, $column);

            if ($first) {
                $first = false;

                switch ($this->checkbox_selectors) {
                    case EnumTableIdColumn::hidden:
                        break;

                    case EnumTableIdColumn::checkbox:
                        $return .= '<td>' . InputCheckbox::new()
                                                         ->setName($column . '[]')
                                                         ->setValue(1)
                                                         ->render() . '
                                    </td>';
                        break;

                    case EnumTableIdColumn::visible:
                        $return .= '<td>' . $footer . '</td>';
                        break;
                }

            } else {
                $return .= '<td>' . $footer . '</td>';
            }
        }

        return $return . '</tr></tfoot>';
    }


    /**
     * Returns true if the specified column renders
     *
     * @param string $column
     *
     * @return bool
     */
    public function renderColumn(string $column): bool
    {
        if ($this->column_cache) {
            return array_key_exists($column, $this->column_cache);
        }

        return true;
    }


    /**
     * Ensures that the headers are available
     *
     * @param array $source
     *
     * @return void
     */
    protected function ensureHeadersAndColumns(array $source): void
    {
        if (!$this->headers) {
            // Auto set headers from the column names, make sure that headers to look pretty for humans
            foreach ($source as $key => &$value) {
                $value = str_replace(['-', '_'], ' ', (string) $key);
                $value = Strings::capitalize($value);

            }

            unset($value);
            $this->setHeaders(new Iterator($source));

        } elseif (!$this->columns) {
            $this->setHeaders(new Iterator($source));
        }
    }
}
