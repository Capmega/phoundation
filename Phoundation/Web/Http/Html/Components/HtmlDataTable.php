<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Http\Html\Enums\Interfaces\PagingTypeInterface;
use Phoundation\Web\Http\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Http\Html\Enums\PagingType;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Page;


/**
 * Class HtmlDataTable
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class HtmlDataTable extends HtmlTable implements HtmlDataTableInterface
{
    /**
     * Enables the Responsive extension for DataTables
     *
     * @var bool|null $responsive_enabled
     */
    protected ?bool $responsive_enabled = false;

    /**
     * Configures the Responsive extension for DataTables
     *
     * @var array|null $responsive_breakpoints
     */
    protected ?array $responsive_breakpoints = null;

    /**
     * Feature control table information display field
     *
     * @var bool|null $info_enabled
     */
    protected ?bool $info_enabled = true;

    /**
     * Feature control search (filtering) abilities
     *
     * @var bool|null $searching_enabled
     */
    protected ?bool $searching_enabled = true;

    /**
     * Control case-sensitive filtering option
     *
     * @var bool|null $search_case_insensitive_enabled
     */
    protected ?bool $search_case_insensitive_enabled = true;

    /**
     * Enable / disable escaping of regular expression characters in the search term.
     *
     * @var bool|null $search_regex_enabled
     */
    protected ?bool $search_regex_enabled = true;

    /**
     * Enable / disable DataTables' smart filtering.
     *
     * @var bool|null $search_smart_enabled
     */
    protected ?bool $search_smart_enabled = true;

    /**
     * Enable / disable DataTables' search on return
     *
     * @var bool|null $search_return_enabled
     */
    protected ?bool $search_return_enabled = false;

    /**
     * Initial filtering condition on the table
     *
     * @var string|null $search
     */
    protected ?string $search = null;

    /**
     * Sets if paging is enabled or disabled
     *
     * @var bool|null $paging_enabled
     */
    protected ?bool $paging_enabled = true;

    /**
     * Pagination button display options
     *
     * @var PagingTypeInterface|null $paging_enabled
     */
    protected ?PagingTypeInterface $paging_type = null;

    /**
     * The menu available to the user displaying the optional paging lengths
     *
     * @var array|null $length_menu
     */
    protected ?array $length_menu = null;

    /**
     * Sets if the length menu is displayed or not
     *
     * @var bool|null $length_change_enabled
     */
    protected ?bool $length_change_enabled = true;

    /**
     * The default page length
     *
     * @var int|null $page_length
     */
    protected ?int $page_length = null;

    /**
     * Initial paging start row.
     *
     * @var int|null $display_start
     */
    protected ?int $display_start = null;

    /**
     * Feature control DataTables' smart column width handling
     *
     * @var bool|null $auto_width_enabled
     */
    protected ?bool $auto_width_enabled = true;

    /**
     * Feature control deferred rendering for additional speed of initialisation
     *
     * @var bool|null $defer_render_enabled
     */
    protected ?bool $defer_render_enabled = false;

    /**
     * Initial order (sort) to apply to the table.
     *
     * @var array|null $order
     */
    protected ?array $order = null;

    /**
     * Ordering to always be applied to the table.
     *
     * @var array|null $order_fixed
     */
    protected ?array $order_fixed = null;

    /**
     * Initial order (sort) to apply to the table.
     *
     * @var array|null $columns_orderable
     */
    protected ?array $columns_orderable = null;

    /**
     * Feature control ordering (sorting) abilities in DataTables
     *
     * @var bool|null $ordering_enabled
     */
    protected ?bool $ordering_enabled = true;

    /**
     * Highlight the columns being ordered in the table's body.
     *
     * @var bool|null $order_classes_enabled
     */
    protected ?bool $order_classes_enabled = true;

    /**
     * Multiple column ordering ability control.
     *
     * @var bool|null $order_multi_enabled
     */
    protected ?bool $order_multi_enabled = false;

    /**
     * Table top-buttons
     *
     * @var array|null $buttons
     */
    protected ?array $buttons = null;


    /**
     * HtmlDataTable class constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Set defaults
        $this
            ->setPagingEnabled(Config::getBoolean('data.paging.enabled', true))
            ->setPagingType(PagingType::from(Config::getString('data.paging.type', 'simple_numbers')))
            ->setPageLength(Config::getInteger('data.paging.limit', 50))
            ->setOrderClassesEnabled(Config::getBoolean('data.paging.order-classes', true))
            ->setButtons(['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'])
            ->setLengthMenu([
                 10 =>  10,
                 25 =>  25,
                 50 =>  50,
                100 => 100,
                 -1 => tr('All')
            ]);
    }


    /**
     * Returns table top-buttons
     *
     * @return array
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }


    /**
     * Sets table top-buttons
     *
     * @param array|string|null $buttons
     * @return $this
     */
    public function setButtons(array|string|null $buttons): static
    {
        // For now only built in buttons are supported
        $builtin = [
            'copy'   => '{ extend: "copy"  , text: "' . tr('Copy') . '" }',
            'csv'    => '{ extend: "csv"   , text: "' . tr('CSV') . '" }',
            'excel'  => '{ extend: "excel" , text: "' . tr('Excel') . '" }',
            'pdf'    => '{ extend: "pdf"   , text: "' . tr('PDF') . '" }',
            'print'  => '{ extend: "print" , text: "' . tr('Print') . '" }',
            'colvis' => '{ extend: "colvis", text: "' . tr('Column visibility') . '" }',
        ];

        // Validate buttons & reformat definition
        $buttons = Arrays::force($buttons);

        foreach ($buttons as &$button) {
            if (!array_key_exists($button, $builtin)) {
                throw new OutOfBoundsException(tr('Unknown button ":button" specified. Please specify one of ":builtin"', [
                    ':button'  => $button,
                    ':builtin' => $builtin
                ]));
            }

            $button = $builtin[$button];
        }

        $this->buttons = $buttons;

        unset($button);
        return $this;
    }


    /**
     * Returns if responsive table is enabled or not
     *
     * @return bool|null
     */
    public function getResponsiveEnabled(): ?bool
    {
        return $this->responsive_enabled;
    }


    /**
     * Sets if responsive table is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setResponsiveEnabled(?bool $enabled): static
    {
        $this->responsive_enabled = $enabled;
        return $this;
    }


    /**
     * Returns responsive breakpoints
     *
     * @return array|null
     */
    public function getResponsiveBreakpoints(): ?array
    {
        return $this->responsive_breakpoints;
    }


    /**
     * Sets responsive breakpoints
     *
     * @param array|null $breakpoints
     * @return $this
     */
    public function setResponsiveBreakpoints(?array $breakpoints): static
    {
        $return = [];

        // Validate given order data and reformat
        foreach ($breakpoints as $column => $width) {
            if (is_integer($width)) {
                if (($width < 0) or ($width > 100_000)) {
                    throw new OutOfBoundsException(tr('Invalid width ":width" specified, must be either "Infinity" or integer between 0 and 100.000', [
                        ':width' => $width
                    ]));
                }
            } else {
                $width = trim($width);
                $width = strtolower($width);

                if ($width !== 'infinity') {
                    throw new OutOfBoundsException(tr('Invalid width ":width" specified, must be either "Infinity" or integer between 0 and 100.000', [
                        ':width' => $width
                    ]));
                }

                $width = 'Infinity';
            }

            $this->responsive_breakpoints[] = '{name: "' . $column . '", width: ' . $width . '}';
        }

        $this->responsive_breakpoints = $breakpoints;
        return $this;
    }


    /**
     * Returns if table information display field is enabled or not
     *
     * @return bool|null
     */
    public function getInfoEnabled(): ?bool
    {
        return $this->info_enabled;
    }


    /**
     * Sets if table information display field is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setInfoEnabled(?bool $enabled): static
    {
        $this->info_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if search (filtering) abilities are enabled or disabled
     *
     * @return bool|null
     */
    public function getSearchingEnabled(): ?bool
    {
        return $this->searching_enabled;
    }


    /**
     * Sets if search (filtering) abilities are enabled or disabled
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchingEnabled(?bool $enabled): static
    {
        $this->searching_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if case-sensitive filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchCaseInsensitiveEnabled(): ?bool
    {
        return $this->search_case_insensitive_enabled;
    }


    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchCaseInsensitiveEnabled(?bool $enabled): static
    {
        $this->search_case_insensitive_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @return bool|null
     */
    public function getSearchRegexEnabled(): ?bool
    {
        return $this->search_regex_enabled;
    }


    /**
     * Sets if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchRegexEnabled(?bool $enabled): static
    {
        $this->search_regex_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if DataTables' smart filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchSmartEnabled(): ?bool
    {
        return $this->search_smart_enabled;
    }


    /**
     * Sets if DataTables' smart filtering is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchSmartEnabled(?bool $enabled): static
    {
        $this->search_smart_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if search on return is enabled or not
     *
     * @return bool|null
     */
    public function getSearchReturnEnabled(): ?bool
    {
        return $this->search_return_enabled;
    }


    /**
     * Sets if search on return is enabled or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setSearchReturnEnabled(?bool $enabled): static
    {
        $this->search_return_enabled = $enabled;
        return $this;
    }


    /**
     * Returns the initial filtering condition on the table
     *
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search;
    }


    /**
     * Sets the initial filtering condition on the table
     *
     * @param string|null $search
     * @return $this
     */
    public function setSearch(?string $search): static
    {
        $this->search = $search;
        return $this;
    }


    /**
     * Returns if paging is enabled or disabled
     *
     * @return bool|null
     */
    public function getPagingEnabled(): ?bool
    {
        return $this->paging_enabled;
    }


    /**
     * Sets if paging is enabled or disabled
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setPagingEnabled(?bool $enabled): static
    {
        $this->paging_enabled = $enabled;
        return $this;
    }


    /**
     * Sets pagination button display options
     *
     * @return PagingTypeInterface
     */
    public function getPagingType(): PagingTypeInterface
    {
        return $this->paging_type;
    }


    /**
     * Sets pagination button display options
     *
     * @param PagingTypeInterface $type
     * @return $this
     */
    public function setPagingType(PagingTypeInterface $type): static
    {
        $this->paging_type = $type;
        return $this;
    }


    /**
     * Returns the menu available to the user displaying the optional paging lengths
     *
     * @return array
     */
    public function getLengthMenu(): array
    {
        return $this->length_menu;
    }


    /**
     * Sets the menu available to the user displaying the optional paging lengths
     *
     * @param array|null $length_menu
     * @return $this
     */
    public function setLengthMenu(?array $length_menu): static
    {
        foreach ($length_menu as &$label) {
            $label = quote($label);
        }

        $this->length_menu = $length_menu;
        unset($label);
        return $this;
    }


    /**
     * Returns if the length menu is displayed or not
     *
     * @return bool|null
     */
    public function getLengthChangeEnabled(): ?bool
    {
        return $this->length_change_enabled;
    }


    /**
     * Sets if the length menu is displayed or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setLengthChangeEnabled(?bool $enabled): static
    {
        $this->length_change_enabled = $enabled;
        return $this;
    }


    /**
     * Returns the default page length
     *
     * @return int
     */
    public function getPageLength(): int
    {
        return $this->page_length;
    }


    /**
     * Sets the default page length
     *
     * @param int $length
     * @return $this
     */
    public function setPageLength(int $length): static
    {
        $this->page_length = $length;
        return $this;
    }


    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @return bool|null
     */
    public function getAutoWidthEnabled(): ?bool
    {
        return $this->auto_width_enabled;
    }


    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setAutoWidthEnabled(?bool $enabled): static
    {
        $this->auto_width_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if deferred rendering for additional speed of initialisation is used
     *
     * @return bool|null
     */
    public function getDeferRenderEnabled(): ?bool
    {
        return $this->defer_render_enabled;
    }


    /**
     * Sets if deferred rendering for additional speed of initialisation is used
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setDeferRenderEnabled(?bool $enabled): static
    {
        $this->defer_render_enabled = $enabled;
        return $this;
    }


    /**
     * Returns initial paging start point.
     *
     * @return int
     */
    public function getDisplayStart(): int
    {
        return $this->display_start;
    }


    /**
     * Sets initial paging start point.
     *
     * @param int $start
     * @return $this
     */
    public function setDisplayStart(int $start): static
    {
        $this->display_start = $start;
        return $this;
    }


    /**
     * Returns initial order (sort) to apply to the table
     *
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }


    /**
     * Sets initial order (sort) to apply to the table
     *
     * @param array|null $order
     * @return $this
     */
    public function setOrder(?array $order): static
    {
        $this->order = $this->reformatOrdering($order);
        return $this;
    }


    /**
     * Returns ordering to always be applied to the table
     *
     * @return array
     */
    public function getOrderFixed(): array
    {
        return $this->order_fixed;
    }


    /**
     * Sets ordering to always be applied to the table
     *
     * @param array|null $order
     * @return $this
     */
    public function setOrderFixed(?array $order): static
    {
        $this->order_fixed = $this->reformatOrdering($order);
        return $this;
    }


    /**
     * Returns the columns that can be ordered
     *
     * @return array
     */
    public function getColumnsOrderable(): array
    {
        return $this->columns_orderable;
    }


    /**
     * Sets the columns that can be ordered
     *
     * @param array|null $columns
     * @return $this
     */
    public function setColumnsOrderable(?array $columns): static
    {
        // Validate content
        foreach ($columns as $key => $value) {
            if (!is_integer($key)) {
                throw new OutOfBoundsException(tr('Specified key ":key" is invalid, keys must be integer', [
                    ':key' => $key
                ]));
            }

            if (!is_bool($value) and ($value !== null)) {
                throw new OutOfBoundsException(tr('Specified key ":key" has invalid value ":value", values must be boolean', [
                    ':key'   => $key,
                    ':value' => $value
                ]));
            }
        }

        $this->columns_orderable = $columns;
        return $this;
    }


    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @return bool|null
     */
    public function getOrderingEnabled(): ?bool
    {
        return $this->ordering_enabled;
    }


    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderingEnabled(?bool $enabled): static
    {
        $this->ordering_enabled = $enabled;
        return $this;
    }


    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @return bool|null
     */
    public function getOrderClassesEnabled(): ?bool
    {
        return $this->order_classes_enabled;
    }


    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderClassesEnabled(?bool $enabled): static
    {
        $this->order_classes_enabled = $enabled;
        return $this;
    }


    /**
     * Returns if multiple column ordering ability is available or not
     *
     * @return bool|null
     */
    public function getOrderMultiEnabled(): ?bool
    {
        return $this->order_multi_enabled;
    }


    /**
     * Sets if multiple column ordering ability is available or not
     *
     * @param bool|null $enabled
     * @return $this
     */
    public function setOrderMultiEnabled(?bool $enabled): static
    {
        $this->order_multi_enabled = $enabled;
        return $this;
    }


    /**
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // TODO Load many of these javascripts conditionally and only if their functions are enabled (button is there, functionality is required, etc)
        Page::loadJavascript([
            'adminlte/plugins/datatables/jquery.dataTables',
            'adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4',
            'adminlte/plugins/datatables-responsive/js/dataTables.responsive',
            'adminlte/plugins/datatables-responsive/js/responsive.bootstrap4',
            'adminlte/plugins/datatables-buttons/js/dataTables.buttons',
            'adminlte/plugins/datatables-buttons/js/buttons.bootstrap4',
            'adminlte/plugins/jszip/jszip',
            'adminlte/plugins/pdfmake/pdfmake',
            'adminlte/plugins/pdfmake/vfs_fonts',
            'adminlte/plugins/datatables-buttons/js/buttons.html5',
            'adminlte/plugins/datatables-buttons/js/buttons.print',
            'adminlte/plugins/datatables-buttons/js/buttons.colVis'
        ]);

        Page::loadCss('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4');
        Page::loadCss('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4');
        Page::loadCss('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4');

        // Build options
        $options = [];

        if ($this->page_length !== null) {
            $options[] = 'pageLength: ' . $this->page_length;
        }

        if ($this->display_start !== null) {
            $options[] = 'displayStart: ' . $this->display_start;
        }

        if ($this->info_enabled !== null) {
            $options[] = 'info: ' . Strings::fromBoolean($this->info_enabled);
        }

        if ($this->searching_enabled !== null) {
            $options[] = 'searching: ' . Strings::fromBoolean($this->searching_enabled);
        }

        if ($this->search_case_insensitive_enabled !== null) {
            $options[] = 'searchCaseInsensitive: ' . Strings::fromBoolean($this->search_case_insensitive_enabled);
        }

        if ($this->search_regex_enabled !== null) {
            $options[] = 'searchRegex: ' . Strings::fromBoolean($this->search_regex_enabled);
        }

        if ($this->search_smart_enabled !== null) {
            $options[] = 'searchSmart: ' . Strings::fromBoolean($this->search_smart_enabled);
        }

        if ($this->search_return_enabled !== null) {
            $options[] = 'searchReturn: ' . Strings::fromBoolean($this->search_return_enabled);
        }

        if ($this->search !== null) {
            $options[] = 'search": "' . ($this->search) . '"';
        }

        if ($this->paging_enabled !== null) {
            $options[] = 'paging: ' . Strings::fromBoolean($this->paging_enabled);
        }

        if ($this->paging_type !== null) {
            $options[] = 'pagingType: "' . $this->paging_type->value . '"';
        }

        if ($this->length_menu !== null) {
            $options[] = 'lengthMenu: ' . $this->getDataTableLengthMenu();
        }

        if ($this->length_change_enabled !== null) {
            $options[] = 'lengthChange: ' . Strings::fromBoolean($this->length_change_enabled);
        }

        if ($this->auto_width_enabled !== null) {
            $options[] = 'autoWidth: ' . Strings::fromBoolean($this->auto_width_enabled);
        }

        if ($this->defer_render_enabled !== null) {
            $options[] = 'deferRender: ' . Strings::fromBoolean($this->defer_render_enabled);
        }

        if ($this->ordering_enabled !== null) {
            $options[] = 'ordering: ' . Strings::fromBoolean($this->ordering_enabled);
        }

        if ($this->order_classes_enabled !== null) {
            $options[] = 'orderClasses: ' . Strings::fromBoolean($this->order_classes_enabled);
        }

        if ($this->order_multi_enabled !== null) {
            $options[] = 'orderMulti: ' . Strings::fromBoolean($this->order_multi_enabled);
        }

        if ($this->order !== null) {
            $options[] = 'order: [' . implode(', ' . PHP_EOL, $this->order) . ']';
        }

        if ($this->order_fixed !== null) {
            $options[] = 'orderFixed: { pre: [' . implode(', ' . PHP_EOL, $this->order_fixed) . '] }';
        }

        if ($this->buttons !== null) {
            $options[] = 'buttons: { buttons: [ ' . implode(', ' . PHP_EOL, $this->buttons) . ' ] }';
        }

        if ($this->responsive) {
            $options[] = $this->getDataTableResponsive();
        }

        if ($this->columns_orderable) {
            $options[] = $this->getDataTableColumnDefinitions();
        }

        $id     = $this->getId();
        $render = Script::new()
            ->setJavascriptWrapper(JavascriptWrappers::dom_content)
            ->setContent('
                $("#' . Html::safe($id) . '").DataTable({
                  ' . implode(', ' . PHP_EOL, $options) . '
                }).buttons().container().appendTo("#' . Html::safe($id) . '_wrapper .col-md-6:eq(0)");')->render();

//showdie('$("#' . Html::safe($id) . '").DataTable({
//                  ' . implode(', ' . PHP_EOL, $options) . '
//                }).buttons().container().appendTo("#' . Html::safe($id) . '_wrapper .col-md-6:eq(0)");');

        return $render . parent::render();
    }


    /**
     * Reformats and returns the given [col0 => order, col1 => order] array to DataTable's [[0, order], [1, order]]
     *
     * @param array $order
     * @return array
     */
    protected function reformatOrdering(array $order): array
    {
        $return = [];

        // Validate given order data and reformat
        foreach ($order as $column => $direction) {
            if (!is_really_integer($column)) {
                throw new OutOfBoundsException(tr('Invalid table order specified. Order key ":column" represents a column index and must be an integer', [
                    ':column' => $column
                ]));
            }

            $direction = trim($direction);
            $direction = strtolower($direction);

            switch ($direction) {
                case 'asc':
                    // no break
                case 'desc':
                    break;

                default:
                    throw new OutOfBoundsException(tr('Invalid table order specified. Order direction ":direction" for column ":column" must be one of "desc" or "asc"', [
                        ':column'    => $column,
                        ':direction' => $direction
                    ]));
            }

            $return[] = $column . ', "' . $direction . '"';
        }

        return $return;
    }


    /**
     * Returns a JSON string for DataTables containing column definition information
     *
     * @return string|null
     */
    protected function getDataTableColumnDefinitions(): ?string
    {
        if (!$this->columns_orderable) {
            return null;
        }

        $highest = Arrays::getHighestKey($this->columns_orderable);
        $columns = [];

        for ($column = 0; $column <= $highest; $column++) {
            if (array_key_exists($column, $this->columns_orderable)) {
                $columns[] = '{ orderable: ' . Strings::fromBoolean($this->columns_orderable[$column]) . ' }';

            } else {
                $columns[] = 'null';
            }
        }

        return 'columns: [' . PHP_EOL . implode(',' . PHP_EOL, $columns) . PHP_EOL . ']';
    }


    /**
     * Returns a JSON string for DataTables containing page length menu definitions
     *
     * @return string
     */
    protected function getDataTableLengthMenu(): string
    {
        return '[ [ ' . implode(', ', array_keys($this->length_menu)) . ' ], [ ' . implode(', ', $this->length_menu) . ' ] ]';
    }


    /**
     * Returns a JSON string for DataTables containing responsiveness definitions
     *
     * @return string|null
     */
    protected function getDataTableResponsive(): ?string
    {
        if (!$this->responsive_enabled) {
            if ($this->responsive_enabled === null) {
                return null;
            }

            return 'responsive: false';
        }

        if ($this->responsive_breakpoints) {
            return 'responsive: {' . PHP_EOL . 'breakpoints: [' . PHP_EOL . implode(',' . PHP_EOL, $this->responsive_breakpoints) . '] }' . PHP_EOL;
        }

        return 'responsive: true';
    }
}
