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
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataCellCallbacks;
use Phoundation\Data\Traits\TraitDataColumns;
use Phoundation\Data\Traits\TraitDataDataIterator;
use Phoundation\Data\Traits\TraitDataRowCallbacks;
use Phoundation\Data\Traits\TraitDataTitle;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputCheckbox;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\ResourceElement;
use Phoundation\Web\Html\Components\ResourceElementCore;
use Phoundation\Web\Html\Components\Tables\Exception\TablesException;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Traits\TraitButtons;
use Phoundation\Web\Http\Url;
use Stringable;


class HtmlTable extends ResourceElement implements HtmlTableInterface
{
    use TraitButtons;
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
     * URLs that apply to all rows
     *
     * @var string|null $row_url
     */
    protected ?string $row_url = null;

    /**
     * The table column headers
     *
     * @var IteratorInterface|null $headers
     */
    protected ?IteratorInterface $headers = null;

    /**
     * The table column footers
     *
     * @var IteratorInterface|null $footers
     */
    protected ?IteratorInterface $footers = null;

    /**
     * URL's specific for columns
     *
     * @var IteratorInterface|null $column_urls
     */
    protected ?IteratorInterface $column_urls = null;

    /**
     * Top buttons
     *
     * @var IteratorInterface|null $top_buttons
     */
    protected ?IteratorInterface $top_buttons = null;

    /**
     * Data attributes for <td> columns
     *
     * @var IteratorInterface|null $column_data_attributes
     */
    protected ?IteratorInterface $column_data_attributes = null;

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
     * @var string|null $header_text
     */
    protected ?string $header_text = null;

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
     * @param DataIteratorInterface|null $o_data_iterator
     *
     * @return static
     */
    public function setDataIteratorObject(?DataIteratorInterface $o_data_iterator): static
    {
        if ($o_data_iterator) {
            $this->setComponentEmptyLabel(tr('No :types available', [
                ':types' => $o_data_iterator->getIteratorName(),
            ]));

        } else {
            $this->setComponentEmptyLabel(tr('No results available'));
        }

        return parent::setDataIteratorObject($o_data_iterator);
    }


    /**
     * Returns if the table is header_text or not
     *
     * @return string|null
     */
    public function getHeaderText(): ?string
    {
        return $this->header_text;
    }


    /**
     * Sets if the table is header_text or not
     *
     * @param string|null $header_text
     *
     * @return static
     */
    public function setHeaderText(?string $header_text): static
    {
        $this->header_text = $header_text;
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
    public function getColumnDataAttributes(): IteratorInterface
    {
        if (empty($this->column_data_attributes)) {
            $this->column_data_attributes = new Iterator();
        }

        return $this->column_data_attributes;
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
     * Returns the table's top buttons
     *
     * @return IteratorInterface
     */
    public function getTopButtons(): IteratorInterface
    {
        if (empty($this->top_buttons)) {
            $this->top_buttons = new Iterator();
        }

        return $this->top_buttons;
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
     * @return string|null
     */
    public function getRowUrl(): ?string
    {
        return $this->row_url;
    }


    /**
     * Sets the URL that applies to each row
     *
     * @param Stringable|string|null $row_url
     *
     * @return static
     */
    public function setRowUrl(Stringable|string|null $row_url): static
    {
        $this->row_url = (string) $row_url;

        return $this;
    }


    /**
     * Returns the table headers
     *
     * @return IteratorInterface
     */
    public function getFooters(): IteratorInterface
    {
        if (empty($this->footers)) {
            $this->footers = new Iterator();
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
        if (is_array($footers)) {
            $footers = Iterator::new()->setSource($footers);
        }

        $this->footers = $footers;

        return $this;
    }


    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string
    {
        $return  = null;
        $return .= $this->renderBodyQuery();
        $return .= $this->renderBodyArray();

        if (!$return) {
            return $this->renderBodyEmpty();
        }

        return $this->renderHeaders() . $return . $this->renderFooters();
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
        return $this->source->get($row, $exception);
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
        return $this->doRenderRow($row_id, $row, $params);
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
        $row_data = '';
        $this->count++;

        // Add data-* in this option?
        if (array_key_exists($row_id, $this->data_source)) {
            $row_data = ' data-' . $row_id . '="' . $this->data_source[$row_id] . '"';
        }

        $cells = null;
        $row   = '<tr' . $row_data . $this->renderRowClassString() . '>';
        $first = true;

        foreach ($this->columns as $column) {
            $value         = isset_get($row_values[$column]);
            $made_checkbox = false;

            if ($first) {
                // Convert first column to checkboxes?
                $value = $this->renderCheckboxColumn($column, $value, $made_checkbox);
                $first = false;

                $params['htmlentities'] = !$made_checkbox;
                $params['no_url']       = (isset_get($params['no_url'], false) or $made_checkbox or !$value);

                // If HtmlTable::renderCheckboxColumn() returned NULL, it means that we should not render this cell
                if ($value !== null) {
                    $this->executeCellCallbacks($row_id, $column, $value, $row_values, $params);

                    $cells .= $this->renderCell($row_id, $column, $value, $params);
                }

            } else {
                $params['no_url'] = false;

                $this->executeCellCallbacks($row_id, $column, $value, $row_values, $params);

                $cells .= $this->renderCell($row_id, $column, $value, $params);
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

        // We've rendered an empty row, return nothing
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
            $this->headers = new Iterator();
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
     * @return string|null
     */
    protected function renderRowClassString(): ?string
    {
        if ($this->row_classes) {
            return ' class="' . $this->row_classes . '"';
        }

        return null;
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
     * @param array                                 $params
     *
     * @return string|null
     */
    protected function renderCell(string|float|int|null $row_id, string|float|int|null $column, Stringable|string|float|int|bool|null $value, array $params): ?string
    {
        if (!$this->renderColumn($column)) {
            return null;
        }

        if (($column === 'status') and $value === null) {
            // Default status label for when status is NULL
            $value = $this->null_status;
        }

        // Use row or column URL's?
        // Use column convert?
        $attributes = '';
        $value      = (string) $value;
        $url        = $this->getColumnUrls()->get($column, false);
        $convert    = $this->getConvertColumns()->get($column, false);

        if (!$url and $this->row_url) {
            $url = $this->row_url;
        }

        if (isset_get($params['no_url'])) {
            $url = null;
        }

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

        if (isset($url)) {
            $value = $this->renderUrl($row_id, $column, $value, $url);
        }

        // Add data attributes?
        if ($this->column_data_attributes) {
            foreach ($this->column_data_attributes as $data_key => $data_value) {
                $attributes .= ' data-' . $data_key . '="' . $data_value . '"';
            }
        }

        // Build row with TD tags with attributes
        return '<td' . $attributes . $this->renderColumnClassString() . '>' . $value . '</td>';
    }


    /**
     * Returns the URL that applies to each column
     *
     * @return IteratorInterface
     */
    public function getColumnUrls(): IteratorInterface
    {
        if (empty($this->column_urls)) {
            $this->column_urls = new Iterator();
        }

        return $this->column_urls;
    }


    /**
     * Returns the table's column conversions
     *
     * @return IteratorInterface
     */
    public function getConvertColumns(): IteratorInterface
    {
        if (empty($this->convert_columns)) {
            $this->convert_columns = new Iterator();
        }

        return $this->convert_columns;
    }


    /**
     * Builds a URL around the specified column value
     *
     * @param mixed  $row_id
     * @param mixed  $column
     * @param string $value
     * @param string $url
     *
     * @return string
     */
    protected function renderUrl(mixed $row_id, mixed $column, string $value, string $url): string
    {
        if ($url) {
            // Ensure all :ROW and :COLUMN markings are converted
            $url = str_replace(':ROW'     , urlencode((string) $row_id), $url);
            $url = str_replace('%3AROW'   , urlencode((string) $row_id), $url);
            $url = str_replace(':COLUMN'  , urlencode((string) $column), $url);
            $url = str_replace('%3ACOLUMN', urlencode((string) $column), $url);

            $attributes = '';

            if ($this->anchor_data_attributes) {
                foreach ($this->anchor_data_attributes as $data_key => $data_value) {
                    $attributes .= ' data-' . $data_key . '="' . $data_value . '"';
                }
            }

            return '<a' . $this->renderAnchorClassString() . ' href="' . Url::new($url)->makeWww() . '"' . $attributes . '>' . $value . '</a>';
        }

        return $url;
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

        foreach ($this->headers as $column => $header) {
            if (!$this->renderColumn($column)) {
                continue;
            }

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
        $footers = $this->footers->__toArray();

        $this->executeRowCallbacks($footers, EnumTableRowType::footer, $params);

        foreach ($footers as $column => $footer) {
            if (!$this->renderColumn($column)) {
                continue;
            }


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
                        $return .= '<td>' . $header . '</td>';
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

        }elseif (!$this->columns) {
            $this->setHeaders(new Iterator($source));
        }
    }
}
