<?php

namespace Phoundation\Web\Http\Html;



use PDO;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Exception\HtmlException;

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
Class Table extends ResourceElement
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
     * The table headers
     *
     * @var array $headers
     */
    protected array $headers = [];



    /**
     * Table constructor
     */
    public function __construct()
    {
        parent::__construct('table');
    }



    /**
     * Sets the HTML row_class element attribute
     *
     * @param array|string|null $row_classes
     * @return Table
     */
    public function setRowClasses(array|string|null $row_classes): self
    {
        $this->row_classes = [];
        return $this->addRowClasses($row_classes);
    }



    /**
     * Sets the HTML row_class element attribute
     *
     * @param array|string|null $row_classes
     * @return Table
     */
    public function addRowClasses(array|string|null $row_classes): self
    {
        foreach (Arrays::force($row_classes, ' ') as $row_class) {
            $this->addRowClass($row_class);
        }

        return $this;
    }



    /**
     * Adds an row_class to the HTML row_class element attribute
     *
     * @param string $row_class
     * @return Table
     */
    public function addRowClass(string $row_class): self
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
     * Sets the table headers
     *
     * @param array $headers
     * @return Table
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = [];
        return $this->addHeaders($headers);
    }



    /**
     * Adds the specified headers to the table headers
     *
     * @param array $headers
     * @return Table
     */
    public function addHeaders(array $headers): self
    {
        foreach (Arrays::force($headers, ' ') as $header) {
            $this->addHeader($header);
        }

        return $this;
    }



    /**
     * Adds a header to the table headers
     *
     * @param string $header
     * @return Table
     */
    public function addHeader(string $header): self
    {
        $this->headers[] = $header;
        return $this;
    }



    /**
     * Returns the table headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }



    /**
     * Render the table
     *
     * @return string
     */
    public function render(): string
    {
        return $this->renderHeaders() . $this->renderBody() . '</table>';
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
        $empty  = true;

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

        return $return;
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
     * @return string The body HTML (all <option> tags) for a <select> tag
     */
    protected function renderBodyArray(): string
    {
        $return = '<tbody>';

        // Process array resource. Go over each row and in each row over each column
        foreach ($this->source as $key => $row_columns) {
            $row_data = '';
            $this->count++;

            // Add data- in this option?
            if (array_key_exists($key, $this->source_data)) {
                foreach ($this->source_data as $data_key => $data_value) {
                    $row_data = ' data-' . $data_key . '="' . $data_value . '"';
                }
            }

            $row = '<tr' . $row_data . '>';

            if (!is_array($row_columns)) {
                throw new OutOfBoundsException(tr('The specified table source array is invalid. Format should be [[header columns][row columns][row columns] ...]'));
            }

            foreach ($row_columns as $column) {
                $row .= '<td' . $this->buildRowClassString() . '>' . htmlentities($column) . '</td>';
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
     * @return string The body HTML (all <option> tags) for a <select> tag
     */
    protected function renderBodyQuery(): string
    {
        $return = '';

        if (!$this->source_query) {
            return '';
        }

        if (!$this->source_query->rowCount()) {
            return '';
        }

        // Process SQL resource
        while ($row = $this->source_query->fetch(PDO::FETCH_NUM)) {
            $this->count++;
            $option_data = '';

            if (!$row[0]) {
                // To avoid select problems with "none" entries, empty id column values are not allowed
                Log::warning(tr('Dropping result ":count" without key from source query ":query"', [
                    ':count' => $this->count,
                    ':query' => $this->source_query->queryString
                ]));
                continue;
            }

            // Add data- in this option?
            if (array_key_exists($row[0], $this->source_data)) {
                foreach ($this->source_data as $key => $value) {
                    $option_data = ' data-' . $key . '="' . $value . '"';
                }
            }

            $return .= '<option' . $this->buildRowClassString() . $this->buildTableedString($row[0]) . ' value="' . htmlentities($row[0]) . '"' . $option_data . '>' . htmlentities($row[1]) . '</option>';
        }

        return $return;
    }



    /**
     * Render an <option> for "this select has no data and is empty"
     *
     * @return string|null
     */
    protected function renderBodyEmpty(): ?string
    {
        // No content (other than maybe the "none available" entry) was added
        if ($this->empty) {
            return '<tr' . $this->buildOptionClassString() . ' selected value=""><td>' . $this->empty . '</td></tr>';
        }

        return null;
    }



    /**
     * Render the table body
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        $return = '<table>';

        return $return;
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
}