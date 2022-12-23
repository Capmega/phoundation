<?php

namespace Phoundation\Web\Http\Html\Components;

use PDO;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Exception\HtmlException;
use Phoundation\Web\Http\Url;


/**
 * Class Table
 *
 * This class can create various HTML tables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Table extends ResourceElement
{
    /**
     * The class for the <row> elements within the <table> element
     *
     * @var array $row_classes
     */
    protected array $row_classes = [];

    /**
     * The HTML class element attribute cache for the <row> element
     *
     * @var string|null
     */
    protected ?string $row_class = null;

    /**
     * The table column headers
     *
     * @var array $column_headers
     */
    protected array $column_headers = [];

    /**
     * URL's specific for columns
     *
     * @var array $column_url
     */
    protected array $column_url = [];

    /**
     * URLs that apply to all rows
     *
     * @var string|null $row_url
     */
    protected ?string $row_url = null;

    /**
     * Top buttons
     *
     * @var array $top_buttons
     */
    protected array $top_buttons = [];

    /**
     * Convert columns to checkboxes, buttons, etc
     *
     * @var array $convert_columns
     */
    protected array $convert_columns = [];

    /**
     * If true, the first (id) column will be checkboxes
     *
     * @var bool $checkbox_selectors
     */
    protected bool $checkbox_selectors = false;



    /**
     * Table constructor
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('table');
    }



    /**
     * Sets the table's column conversions
     *
     * @param array|string|null $convert_columns
     * @return static
     */
    public function setConvertColumns(array|string|null $convert_columns): static
    {
        $this->convert_columns = [];
        return $this->addConvertColumns($convert_columns);
    }



    /**
     * Adds multiple table column conversions
     *
     * @param array|string|null $convert_columns
     * @return static
     */
    public function addConvertColumns(array|string|null $convert_columns): static
    {
        foreach (Arrays::force($convert_columns, ' ') as $column => $callback) {
            $this->addConvertColumn($column, $callback);
        }

        return $this;
    }



    /**
     * Adds single table column conversions
     *
     * @param string $column
     * @param string|callable $replace_or_callback
     * @return static
     */
    public function addConvertColumn(string $column, string|callable $replace_or_callback): static
    {
        $this->convert_columns[$column] = $replace_or_callback;
        return $this;
    }



    /**
     * Returns the table's column conversions
     *
     * @return array
     */
    public function getConvertColumns(): array
    {
        return $this->convert_columns;
    }



    /**
     * Sets the table's  top buttons
     *
     * @param array|string|null $top_buttons
     * @return static
     */
    public function setTopButtons(array|string|null $top_buttons): static
    {
        $this->top_buttons = [];
        return $this->addTopButtons($top_buttons);
    }



    /**
     * Adds multiple buttons to the table's top buttons
     *
     * @param array|string|null $top_buttons
     * @return static
     */
    public function addTopButtons(array|string|null $top_buttons): static
    {
        foreach (Arrays::force($top_buttons, ' ') as $row_class) {
            $this->addRowClass($row_class);
        }

        return $this;
    }



    /**
     * Adds single button to the table's top buttons
     *
     * @param string $row_class
     * @return static
     */
    public function addTopButton(string $row_class): static
    {
        $this->top_buttons[] = $row_class;
        return $this;
    }



    /**
     * Returns the table's top buttons
     *
     * @return array
     */
    public function getTopButtons(): array
    {
        return $this->top_buttons;
    }



    /**
     * Sets the HTML row_class element attribute
     *
     * @param array|string|null $row_classes
     * @return static
     */
    public function setRowClasses(array|string|null $row_classes): static
    {
        $this->row_classes = [];
        return $this->addRowClasses($row_classes);
    }



    /**
     * Sets the HTML row_class element attribute
     *
     * @param array|string|null $row_classes
     * @return static
     */
    public function addRowClasses(array|string|null $row_classes): static
    {
        foreach (Arrays::force($row_classes, ' ') as $row_class) {
            $this->addRowClass($row_class);
        }

        return $this;
    }



    /**
     * Adds a row_class to the HTML row_class element attribute
     *
     * @param string $row_class
     * @return static
     */
    public function addRowClass(string $row_class): static
    {
        $this->row_classes[] = $row_class;
        return $this;
    }



    /**
     * Returns the HTML row_class element attribute
     *
     * @return array
     */
    public function getRowClasses(): array
    {
        return $this->row_classes;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getRowClass(): ?string
    {
        if (!$this->row_class) {
            $this->row_class = implode(' ', $this->row_classes);
        }

        return $this->row_class;
    }



    /**
     * Returns the URL that applies to each column
     *
     * @return array
     */
    public function getColumnUrl(): array
    {
        return $this->column_url;
    }



    /**
     * Sets the URL that applies to each column
     *
     * @param string $column
     * @param string $url
     * @return static
     */
    public function setColumnUrl(string $column, string $url): static
    {
        $this->column_url[$column] = $url;
        return $this;
    }



    /**
     * Returns if the first column will automatically be converted to checkboxes
     *
     * @return bool
     */
    public function getCheckboxSelectors(): bool
    {
        return $this->checkbox_selectors;
    }



    /**
     * Sets if the first column will automatically be converted to checkboxes
     *
     * @param bool $checkbox_selectors
     * @return static
     */
    public function setCheckboxSelectors(bool $checkbox_selectors): static
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
     * @param string|null $row_url
     * @return static
     */
    public function setRowUrl(string|null $row_url): static
    {
        $this->row_url = $row_url;
        return $this;
    }



    /**
     * Sets the table headers
     *
     * @param array $headers
     * @return static
     */
    public function setColumnHeaders(array $headers): static
    {
        $this->column_headers = [];
        return $this->addColumnHeaders($headers);
    }



    /**
     * Adds the specified headers to the table headers
     *
     * @param array $headers
     * @return static
     */
    public function addColumnHeaders(array $headers): static
    {
        foreach (Arrays::force($headers, ' ') as $header) {
            $this->addColumnHeader($header);
        }

        return $this;
    }



    /**
     * Adds a header to the table headers
     *
     * @param string|null $header
     * @return static
     */
    public function addColumnHeader(?string $header): static
    {
        if ($header) {
            $this->column_headers[] = $header;
        }

        return $this;
    }



    /**
     * Returns the table headers
     *
     * @return array
     */
    public function getColumnHeaders(): array
    {
        return $this->column_headers;
    }



    /**
     * Render the table body
     *
     * @return string
     */
    public function renderBody(): string
    {
        if (($this->source === null) and ($this->source_query === null)) {
            throw new HtmlException(tr('No source specified'));
        }

        $return = '';

        if (($this->source === null) and ($this->source_query === null)) {
            throw new HtmlException(tr('No source specified'));
        }

        if ($this->none) {
            // Add the none element as an array source
            $this->source[''] = [$this->none];
        }

        $return .= $this->renderBodyQuery();
        $return .= $this->renderBodyArray();

        if (!$return) {
            $return = $this->renderBodyEmpty();
        }

        return $this->renderHeaders() . $return;
    }



    /**
     * Generates and returns the HTML string for a table body from an array source
     *
     * This will return all HTML FROM the <tbody> tags around it
     *
     * @see Element::render()
     * @see Table::render()
     * @see Table::renderHeaders()
     * @see ResourceElement::renderBody()
     * @return string|null The body HTML (all <option> tags) for a <select> tag
     */
    protected function renderBodyArray(): ?string
    {
        if (!$this->source) {
            return null;
        }

        $return = '<tbody>';

        // Process array resource. Go over each row and in each row over each column
        foreach ($this->source as $key => $row_values) {
            if (!is_array($row_values)) {
                if (!is_object($row_values) or !method_exists($row_values, '__toArray')) {
                    throw new OutOfBoundsException(tr('The specified table source array is invalid. Format should be [[header columns][row columns][row columns] ...], a ":type" was encountered instead', [
                        ':type' => gettype($row_values)
                    ]));
                }

                $row_values = $row_values->__toArray();
            }

            $row_data = '';
            $this->count++;

            // Add data-* in this option?
            if (array_key_exists($key, $this->source_data)) {
                $row_data = ' data-' . $key . '="' . $this->source_data[$key] . '"';
            }

            $row   = '<tr' . $row_data . $this->buildRowClassString() . '>';
            $first = true;

            foreach ($row_values as $column => $value) {
                if ($first) {
                    // Convert first column to checkboxes?
                    $value = $this->renderCheckboxColumn($column, $value);
                    $row  .= $this->renderCell($key, $column, $value, false);
                    $first = false;
                } else {
                    $row .= $this->renderCell($key, $column, $value);
                }
            }

            $return .= $row . '</tr>';
        }

        return $return . '</tbody>';
    }



    /**
     * Generates and returns the HTML string for a table body from an array source
     *
     * This will return all HTML FROM the <tbody> tags around it
     *
     * @see Element::render()
     * @see Table::render()
     * @see Table::renderHeaders()
     * @see ResourceElement::renderBody()
     * @return string|null The body HTML (all <option> tags) for a <select> tag
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
        while ($row_values = $this->source_query->fetch(PDO::FETCH_ASSOC)) {
            $return .= $this->renderRow($row_values);
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
     * Render the table body
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        $return = '<thead><tr>';

        foreach ($this->column_headers as $header) {
            $return .= '<th>' . $header . '</th>';
        }

        return $return . '</tr></thead>';
    }



    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function buildRowClassString(): ?string
    {
        $row_class = $this->getRowClass();

        if ($row_class) {
            return ' class="' . $row_class . '"';
        }

        return null;
    }



    /**
     * Returns a table cell
     *
     * @param array $row_values
     * @param string|int|null $row_id
     * @return string
     */
    protected function renderRow(array $row_values, string|int|null $row_id = null): string
    {
        if (empty($this->column_headers)) {
            // Auto set headers from the column names
            $this->column_headers = array_keys($row_values);

            foreach ($this->column_headers as &$column_header) {
                $column_header = str_replace(['-', '_'], ' ', $column_header);
                $column_header = Strings::capitalize($column_header);
            }

            unset($column_header);
        }

        // If row identifier was not specified, then assume its the first value in the row
        if ($row_id === null) {
            $row_id = reset($row_values);
        }

        // Add data-* in this option?
//        if (array_key_exists($row_id, $this->source_data)) {
//            $row_data = ' data-' . $key . '="' . $this->source_data[$key] . '"';
//        }

        $return = '<tr>';
        $first  = true;

        foreach($row_values as $column => $value) {
            if ($first) {
                // Convert first column to checkboxes?
                $value   = $this->renderCheckboxColumn($column, $value);
                $return .= $this->renderCell($row_id, $column, $value, false);
                $first = false;
            } else {
                $return .= $this->renderCell($row_id, $column, $value);
            }
        }

        return $return . '</tr>';
    }



    /**
     * Returns a table cell
     *
     * @param string $row_id
     * @param string|int $column
     * @param string|null $value
     * @param bool $entities
     * @return string
     */
    protected function renderCell(string $row_id, string|int $column, ?string $value, bool $entities = true): string
    {
        $value = (string) $value;

        // Do we have row or column URL's?
        if (array_key_exists($column, $this->column_url)) {
            // Use this column specific URL
            $url = $this->column_url;

        } elseif ($this->row_url) {
            $url = $this->row_url;
        }

        if (array_key_exists($column, $this->convert_columns)) {
            if (is_callable($this->convert_columns[$column])) {
                // Convert this column
                $converted = $this->convert_columns[$column]($value);

                if (!is_string($converted)) {
                    throw new OutOfBoundsException(tr('Conversion for column ":column" callback does not return a string as required', [
                        ':column' => $column
                    ]));
                }

                $value = $converted;

            } else {
                // Convert this column
                $value = str_replace(':ROW'   , $this->convert_columns[$column], $value);
                $value = str_replace(':COLUMN', $this->convert_columns[$column], $value);
            }
        } else {
            if ($entities) {
                $value = htmlentities($value);
            }
        }

        if (isset($url)) {
            // Apply URL row / column specific information
            $url = str_replace(':ROW'   , $row_id, $url);
            $url = str_replace(':COLUMN', $column, $url);
            $url = Url::build($url)->www();

            return '<td><a href="' . $url . '">' . $value . '</a></td>';
        }

        return '<td>' . $value . '</td>';
    }



    /**
     * Changes the first column to a checkbox
     *
     * @param string $column
     * @param string $value
     * @return string
     */
    protected function renderCheckboxColumn(string $column, string $value): string
    {
        if (!$this->checkbox_selectors) {
            return $value;
        }

        return CheckBox::new()
            ->setName($column)
            ->setValue($value)
            ->render();
    }
}