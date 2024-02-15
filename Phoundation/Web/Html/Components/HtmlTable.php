<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDO;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataCallbacks;
use Phoundation\Data\Traits\DataTitle;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputCheckbox;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\Interfaces\EnumTableIdColumnInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * Class HtmlTable
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class HtmlTable extends ResourceElement implements HtmlTableInterface
{
    use DataTitle;
    use DataCallbacks;


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
     * @var EnumTableIdColumnInterface $checkbox_selectors
     */
    protected EnumTableIdColumnInterface $checkbox_selectors = EnumTableIdColumn::hidden;

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
     * If specified, the table will filter out these columns from the data source, regardless of if they contain more
     *
     * @var array $columns
     */
    protected array $columns;


    /**
     * Table constructor
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('table');
        $this->setNullStatus(tr('Active'));
    }


    /**
     * Returns the columns specified for this table
     *
     * @return array|null
     */
    public function getColumns(): ?array
    {
        if (isset($this->columns)) {
            return $this->columns;
        }

        return null;
    }


    /**
     * Sets if the table is header_text or not
     *
     * @param ArrayableInterface|array|string|null $columns
     * @return static
     */
    public function setColumns(ArrayableInterface|array|string|null $columns): static
    {
        $columns = Arrays::force($columns);

        if ($columns instanceof ArrayableInterface) {
            $columns = $columns->__toArray();
        }

        $this->columns = $columns;
        return $this;
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
     * Sets if the table will process entities in the source data or not
     *
     * @param bool $process_entities
     * @return static
     */
    public function setProcessEntities(bool $process_entities): static
    {
        $this->process_entities = $process_entities;
        return $this;
    }


    /**
     * Sets if the table will process entities in the source data or not
     *
     * @return bool
     */
    public function getProcessEntities(): bool
    {
        return $this->process_entities;
    }


    /**
     * Sets if the table is responsive or not
     *
     * @param bool $responsive
     * @return static
     */
    public function setResponsive(bool $responsive): static
    {
        $this->responsive = $responsive;
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
     * @return EnumTableIdColumnInterface
     */
    public function getCheckboxSelectors(): EnumTableIdColumnInterface
    {
        return $this->checkbox_selectors;
    }


    /**
     * Sets if the first column automatically is converted to checkboxes
     *
     * @param EnumTableIdColumnInterface $checkbox_selectors
     * @return static
     */
    public function setCheckboxSelectors(EnumTableIdColumnInterface $checkbox_selectors): static
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
     * @return static
     */
    public function setRowUrl(Stringable|string|null $row_url): static
    {
        $this->row_url = (string) $row_url;
        return $this;
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
        $return = null;
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
     * @see \Templates\AdminLte\Html\Components\HtmlTable::render()
     * @see \Templates\AdminLte\Html\Components\HtmlTable::renderHeaders()
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
        foreach ($this->source as $key => $row) {
            if (!is_array($row)) {
                if (!$row instanceof ArrayableInterface) {
                    throw new OutOfBoundsException(tr('The specified table source array is invalid. Format should be [[header columns][row columns][row columns] ...] or contain an object with ArreableInterface Interface. a ":type" was encountered instead', [
                        ':type' => gettype($row)
                    ]));
                }

                // Row values is actually an object, get its content
                $row = $row->__toArray();
            }

            if (isset($this->columns)) {
                $row = Arrays::keepKeys($row, $this->columns);
            }

            $this->executeCallbacks($row, EnumTableRowType::row, $params);
            $return .= $this->renderRow($row, $key, $params);

//            $row_data = '';
//            $this->count++;
//
//            // Add data-* in this option?
//            if (array_key_exists($key, $this->source_data)) {
//                $row_data = ' data-' . $key . '="' . $this->source_data[$key] . '"';
//            }
//
//            $row   = '<tr' . $row_data . $this->renderRowClassString() . '>';
//            $first = true;
//
//            foreach ($row_values as $column => $value) {
//                if ($first) {
//                    // Convert first column to checkboxes?
//                    $value = $this->renderCheckboxColumn($column, $value);
//                    $row  .= $this->renderCell($key, $column, $value, $params);
//                    $first = false;
//
//                } else {
//                    $row .= $this->renderCell($key, $column, $value, $params);
//                }
//            }
//
//            $return .= $row . '</tr>';
        }

        return $return . '</tbody>';
    }


    /**
     * Generates and returns the HTML string for a table body from an array source
     *
     * This will return all HTML FROM the <tbody> tags around it
     *
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     *@see \Templates\AdminLte\Html\Components\HtmlTable::render()
     * @see \Templates\AdminLte\Html\Components\HtmlTable::renderHeaders()
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
        while ($row = $this->source_query->fetch(PDO::FETCH_ASSOC)) {
            $this->executeCallbacks($row, EnumTableRowType::row, $params);
            $return .= $this->renderRow($row, array_first($row), $params);
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
        if ($this->empty) {
            return '<tr><td>' . $this->empty . '</td></tr>';
        }

        return '';
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
            if ($first) {
                $first = false;

                switch ($this->checkbox_selectors) {
                    case EnumTableIdColumn::hidden:
                        break;

                    case EnumTableIdColumn::checkbox:
                        $return .= '<th>' . InputCheckbox::new()
                            ->setName($column . '[]')
                            ->setValue(1)
                            ->render() . '</th>';
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

        $return  = '<tfoot><tr>';
        $footers = $this->footers->__toArray();

        $this->executeCallbacks($footers, EnumTableRowType::footer, $params);

        foreach ($footers as $column => $footer) {
            $return .= '<th>' . $footer . '</th>';
        }

        return $return . '</tr></tfoot>';
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
     * Returns a table row
     *
     * @param array $row_values
     * @param string|float|int|null $key
     * @param array $params
     * @return string
     */
    protected function renderRow(array $row_values, string|float|int|null $key, array $params): string
    {
        if (!$this->headers) {
            // Auto set headers from the column names
            $this->getHeaders()->setSource(array_keys($row_values));

            foreach ($this->headers as $key => $column_header) {
                $column_header = str_replace(['-', '_'], ' ', $column_header);
                $column_header = Strings::capitalize($column_header);

                $this->headers->set($column_header, $key);
            }
        }

        // ID is the first value in the row
        $row_id = reset($row_values);

        $row_data = '';
        $this->count++;

        // Add data-* in this option?
        if (array_key_exists($key, $this->source_data)) {
            $row_data = ' data-' . $key . '="' . $this->source_data[$key] . '"';
        }

        $return = '<tr' . $row_data . $this->renderRowClassString() . '>';
        $first  = true;

        foreach($row_values as $column => $value) {
            $made_checkbox = false;

            if ($first) {
                // Convert first column to checkboxes?
                $value = $this->renderCheckboxColumn($column, $value, $made_checkbox);
                $first = false;

                $params['no_url'] = ($made_checkbox or !$value);

                // If HtmlTable::renderCheckboxColumn() returned NULL, it means that we should not render this cell
                if ($value !== null) {
                    $return .= $this->renderCell($row_id, $column, $value, $params);
                }

            } else {
                $params['no_url'] = $made_checkbox;
                $return .= $this->renderCell($row_id, $column, $value, $params);
            }
        }

        return $return . '</tr>';
    }


    /**
     * Returns a table cell
     *
     * @param string|float|int|null $row_id
     * @param string|float|int|null $column
     * @param Stringable|string|float|int|bool|null $value
     * @param array $params
     * @return string
     */
    protected function renderCell(string|float|int|null $row_id, string|float|int|null $column, Stringable|string|float|int|bool|null $value, array $params): string
    {
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
                        ':column' => $column
                    ]));
                }

                $value = $converted;
            }

        } else {
            if ($params['htmlentities'] and empty($params['skiphtmlentities'][$column])) {
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
     * Changes the first column to a checkbox
     *
     * @param string $column
     * @param string|float|int|null $value
     * @param bool $made_checkbox
     * @return string|null
     */
    protected function renderCheckboxColumn(string $column, string|float|int|null $value, bool &$made_checkbox): string|null
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
     * Builds a URL around the specified column value
     *
     * @param mixed $row_id
     * @param mixed $column
     * @param string $value
     * @param string $url
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

            return '<a' . $this->renderAnchorClassString() . ' href="' . UrlBuilder::getWww($url) . '"' . $attributes . '>' . $value . '</a>';
        }

        return $url;
    }
}
