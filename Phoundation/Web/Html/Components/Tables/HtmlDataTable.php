<?php

/**
 * Class HtmlDataTable
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Tables;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Date\DateFormats;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Enums\EnumAttachJavascript;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Html\Enums\EnumPagingType;
use Phoundation\Web\Html\Enums\EnumTableRowType;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Requests\Response;


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
     * @var EnumPagingType|null $paging_enabled
     */
    protected ?EnumPagingType $paging_type = null;

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
     * Date format for data table ordering
     *
     * @var string|null $js_date_format
     */
    protected ?string $js_date_format = null;

    /**
     * Sets the date format for PHP
     */
    protected ?string $php_date_format = null;


    /**
     * HtmlDataTable class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        // Set defaults
        $this->setPagingEnabled(Config::getBoolean('data.paging.enabled', true))
             ->setPagingType(EnumPagingType::from(Config::getString('data.paging.type', 'simple_numbers')))
             ->setPageLength(Config::getInteger('data.paging.limit', 25))
             ->setOrderClassesEnabled(Config::getBoolean('data.paging.order-classes', true))
             ->setButtons([
                 'copy',
                 'csv',
                 'excel',
                 'pdf',
                 'print',
                 'colvis',
             ])
             ->addRowCallback(function (IteratorInterface|array &$row, EnumTableRowType $type, &$params) {
                 if (isset($row['created_on'])) {
                     $row['created_on'] = DateTime::new($row['created_on'])
                                                  ->setTimezone('user')
                                                  ->format($this->php_date_format);
                 }
             })
             ->setLengthMenu([
                 10  => 10,
                 25  => 25,
                 50  => 50,
                 100 => 100,
                 -1  => tr('All'),
             ]);

        $this->js_date_format  = 'YYYY-MM-DD HH:mm:ss';
        $this->php_date_format = 'Y-m-d H:i:s';
    }


    /**
     * Returns table top-buttons
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        return $this->buttons;
    }


    /**
     * Sets table top-buttons
     *
     * @param ButtonsInterface|array|string|null $buttons
     *
     * @return static
     */
    public function setButtons(ButtonsInterface|array|string|null $buttons): static
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
        $source  = [];

        foreach ($buttons as $button) {
            if (!array_key_exists($button, $builtin)) {
                throw new OutOfBoundsException(tr('Unknown button ":button" specified. Please specify one of ":builtin"', [
                    ':button'  => $button,
                    ':builtin' => $builtin,
                ]));
            }

            $source['"' . $button . '"'] = $builtin[$button];
        }

        $this->buttons = new Buttons($source);

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
     *
     * @return static
     */
    public function setResponsiveEnabled(?bool $enabled): static
    {
        $this->responsive_enabled = $enabled;

        return $this;
    }


    /**
     * Returns date format for date ordering
     *
     * Token               Output
     *  Month                M                   1 2 ... 11 12
     *                       Mo                  1st 2nd ... 11th 12th
     *                       MM                  01 02 ... 11 12
     *                       MMM                 Jan Feb ... Nov Dec
     *                       MMMM                January February ... November December
     *  Quarter              Q                   1 2 3 4
     *                       Qo                  1st 2nd 3rd 4th
     *  Day of Month         D                   1 2 ... 30 31
     *                       Do                  1st 2nd ... 30th 31st
     *                       DD                  01 02 ... 30 31
     *  Day of Year          DDD                 1 2 ... 364 365
     *                       DDDo                1st 2nd ... 364th 365th
     *                       DDDD                001 002 ... 364 365
     *  Day of Week          d                   0 1 ... 5 6
     *                       do                  0th 1st ... 5th 6th
     *                       dd                  Su Mo ... Fr Sa
     *                       ddd                 Sun Mon ... Fri Sat
     *                       dddd                Sunday Monday ... Friday Saturday
     *  Day of Week (Locale) e                   0 1 ... 5 6
     *  Day of Week (ISO)    E                   1 2 ... 6 7
     *  Week of Year         w                   1 2 ... 52 53
     *                       wo                  1st 2nd ... 52nd 53rd
     *                       ww                  01 02 ... 52 53
     *  Week of Year (ISO)   W                   1 2 ... 52 53
     *                       Wo                  1st 2nd ... 52nd 53rd
     *                       WW                  01 02 ... 52 53
     *  Year                 YY                  70 71 ... 29 30
     *                       YYYY                1970 1971 ... 2029 2030
     *                       YYYYYY              -001970 -001971 ... +001907 +001971
     *                                       Note: Expanded Years (Covering the full time value range of approximately
     *                                             273,790 years forward or backward from 01 January, 1970)
     *                       Y                   1970 1971 ... 9999 +10000 +10001
     *                                       Note: This complies with the ISO 8601 standard for dates past the year 9999
     *  Era Year             y                   1 2 ... 2020 ...
     *  Era                  N, NN, NNN          BC AD
     *                                       Note: Abbr era name
     *                       NNNN                Before Christ, Anno Domini
     *                                       Note: Full era name
     *                       NNNNN               BC AD
     *                                       Note: Narrow era name
     *  Week Year            gg                  70 71 ... 29 30
     *                       gggg                1970 1971 ... 2029 2030
     *  Week Year (ISO)      GG                  70 71 ... 29 30
     *                       GGGG                1970 1971 ... 2029 2030
     *  AM/PM                A                   AM PM
     *                       a                   am pm
     *  Hour                 H                   0 1 ... 22 23
     *                       HH                  00 01 ... 22 23
     *                       h                   1 2 ... 11 12
     *                       hh                  01 02 ... 11 12
     *                       k                   1 2 ... 23 24
     *                       kk                  01 02 ... 23 24
     *  Minute               m                   0 1 ... 58 59
     *                       mm                  00 01 ... 58 59
     *  Second               s                   0 1 ... 58 59
     *                       ss                  00 01 ... 58 59
     *  Fractional Second    S                   0 1 ... 8 9
     *                       SS                  00 01 ... 98 99
     *                       SSS                 000 001 ... 998 999
     *                       SSSS ... SSSSSSSSS  000[0..] 001[0..] ... 998[0..] 999[0..]
     *  Time Zone            z or zz             EST CST ... MST PST
     *                                       Note: as of 1.6.0, the z/zz format tokens have been deprecated from plain
     *                                             moment objects. Read more about it here. However, they *do* work if
     *                                             you are using a specific time zone with the moment-timezone addon.
     *                       Z                   -07:00 -06:00 ... +06:00 +07:00
     *                       ZZ                  -0700 -0600 ... +0600 +0700
     *  Unix Timestamp       X                   1360013296
     *  Unix Millisecond     x                   1360013296123
     *       Timestamp
     *
     * @see https://momentjs.com/docs/#/displaying/format/
     * @return string|null
     */
    public function getJsDateFormat(): ?string
    {
        return $this->js_date_format;
    }


    /**
     * Sets date format for date ordering
     *
     *                      Token               Output
     * Month                M                   1 2 ... 11 12
     *                      Mo                  1st 2nd ... 11th 12th
     *                      MM                  01 02 ... 11 12
     *                      MMM                 Jan Feb ... Nov Dec
     *                      MMMM                January February ... November December
     * Quarter              Q                   1 2 3 4
     *                      Qo                  1st 2nd 3rd 4th
     * Day of Month         D                   1 2 ... 30 31
     *                      Do                  1st 2nd ... 30th 31st
     *                      DD                  01 02 ... 30 31
     * Day of Year          DDD                 1 2 ... 364 365
     *                      DDDo                1st 2nd ... 364th 365th
     *                      DDDD                001 002 ... 364 365
     * Day of Week          d                   0 1 ... 5 6
     *                      do                  0th 1st ... 5th 6th
     *                      dd                  Su Mo ... Fr Sa
     *                      ddd                 Sun Mon ... Fri Sat
     *                      dddd                Sunday Monday ... Friday Saturday
     * Day of Week (Locale) e                   0 1 ... 5 6
     * Day of Week (ISO)    E                   1 2 ... 6 7
     * Week of Year         w                   1 2 ... 52 53
     *                      wo                  1st 2nd ... 52nd 53rd
     *                      ww                  01 02 ... 52 53
     * Week of Year (ISO)   W                   1 2 ... 52 53
     *                      Wo                  1st 2nd ... 52nd 53rd
     *                      WW                  01 02 ... 52 53
     * Year                 YY                  70 71 ... 29 30
     *                      YYYY                1970 1971 ... 2029 2030
     *                      YYYYYY              -001970 -001971 ... +001907 +001971
     *                                      Note: Expanded Years (Covering the full time value range of approximately
     *                                            273,790 years forward or backward from 01 January, 1970)
     *                      Y                   1970 1971 ... 9999 +10000 +10001
     *                                      Note: This complies with the ISO 8601 standard for dates past the year 9999
     * Era Year             y                   1 2 ... 2020 ...
     * Era                  N, NN, NNN          BC AD
     *                                      Note: Abbr era name
     *                      NNNN                Before Christ, Anno Domini
     *                                      Note: Full era name
     *                      NNNNN               BC AD
     *                                      Note: Narrow era name
     * Week Year            gg                  70 71 ... 29 30
     *                      gggg                1970 1971 ... 2029 2030
     * Week Year (ISO)      GG                  70 71 ... 29 30
     *                      GGGG                1970 1971 ... 2029 2030
     * AM/PM                A                   AM PM
     *                      a                   am pm
     * Hour                 H                     0   1 ... 22 23
     *                      HH                   00  01 ... 22 23
     *                      h                     1   2 ... 11 12
     *                      hh                   01  02 ... 11 12
     *                      k                     1   2 ... 23 24
     *                      kk                   01  02 ... 23 24
     * Minute               m                     0   1 ... 58 59
     *                      mm                   00  01 ... 58 59
     * Second               s                     0   1 ... 58 59
     *                      ss                   00  01 ... 58 59
     * Fractional Second    S                     0   1 ... 8 9
     *                      SS                   00  01 ... 98 99
     *                      SSS                 000 001 ... 998 999
     *                      SSSS ... SSSSSSSSS  000[0..] 001[0..] ... 998[0..] 999[0..]
     * Time Zone            z or zz             EST CST ... MST PST
     *                                      Note: as of 1.6.0, the z/zz format tokens have been deprecated from plain
     *                                            moment objects. Read more about it here. However, they *do* work if
     *                                            you are using a specific time zone with the moment-timezone addon.
     *                      Z                   -07:00 -06:00 ... +06:00 +07:00
     *                      ZZ                  -0700 -0600 ... +0600 +0700
     * Unix Timestamp       X                   1360013296
     * Unix Millisecond     x                   1360013296123
     *      Timestamp
     *
     * @see https://momentjs.com/docs/#/displaying/format/
     *
     * @param string|null $date_format
     *
     * @return static
     */
    public function setJsDateFormat(?string $date_format): static
    {
        $this->js_date_format  = $date_format;
        $this->php_date_format = DateFormats::convertJsToPhp($date_format);

        return $this;
    }


    /**
     * Returns date format for date ordering
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     * @return string|null
     */
    public function getPhpDateFormat(): ?string
    {
        return $this->js_date_format;
    }


    /**
     * Sets date format for date ordering
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     *
     * @param string|null $php_date_format
     *
     * @return static
     */
    public function setPhpDateFormat(?string $php_date_format): static
    {
        $this->php_date_format = $php_date_format;
        $this->js_date_format  = DateFormats::convertPhpToJs($php_date_format);

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
     *
     * @return static
     */
    public function setResponsiveBreakpoints(?array $breakpoints): static
    {
        $return = [];
        // Validate given order data and reformat
        foreach ($breakpoints as $column => $width) {
            if (is_integer($width)) {
                if (($width < 0) or ($width > 100_000)) {
                    throw new OutOfBoundsException(tr('Invalid width ":width" specified, must be either "Infinity" or integer between 0 and 100.000', [
                        ':width' => $width,
                    ]));
                }
            } else {
                $width = trim($width);
                $width = strtolower($width);
                if ($width !== 'infinity') {
                    throw new OutOfBoundsException(tr('Invalid width ":width" specified, must be either "Infinity" or integer between 0 and 100.000', [
                        ':width' => $width,
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function setPagingEnabled(?bool $enabled): static
    {
        $this->paging_enabled = $enabled;

        return $this;
    }


    /**
     * Sets pagination button display options
     *
     * @return EnumPagingType
     */
    public function getPagingType(): EnumPagingType
    {
        return $this->paging_type;
    }


    /**
     * Sets pagination button display options
     *
     * @param EnumPagingType $type
     *
     * @return static
     */
    public function setPagingType(EnumPagingType $type): static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     * Sets initial order (sort) to apply to the table with
     *
     * Order is specified with [col0 => order, col1 => order] where col is the NUMERICAL COLUMN, and order is either
     * asc / up, or desc / down
     *
     * @param array|null $order
     * @todo Add support for named columns that translate to numerical columns
     *
     * @return static
     */
    public function setOrder(?array $order): static
    {
        $this->order = $this->reformatOrdering($order);

        return $this;
    }


    /**
     * Reformats and returns the given [col0 => order, col1 => order] array to DataTable's [[0, order], [1, order]]
     *
     * @param array $order
     *
     * @return array
     */
    protected function reformatOrdering(array $order): array
    {
        $return = [];

        // Validate given order data and reformat
        foreach ($order as $column => $direction) {
            if (!is_really_integer($column)) {
                throw new OutOfBoundsException(tr('Invalid table order specified. Order key ":column" represents a column index and must be an integer', [
                    ':column' => $column,
                ]));
            }

            $direction = trim($direction);
            $direction = strtolower($direction);

            switch ($direction) {
                case 'up':
                    // no break;

                case 'asc':
                    $direction = 'asc';
                    break;

                case 'down':
                    // no break

                case 'desc':
                    $direction = 'desc';
                    break;

                default:
                    throw new OutOfBoundsException(tr('Invalid table order specified. Order direction ":direction" for column ":column" must be one of "desc" or "asc"', [
                        ':column'    => $column,
                        ':direction' => $direction,
                    ]));
            }

            $return[] = $column . ', "' . $direction . '"';
        }

        return $return;
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
     *
     * @return static
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
     * @todo Add support for named columns that translate to numerical columns
     *
     * @return static
     */
    public function setColumnsOrderable(?array $columns): static
    {
        // Validate content
        foreach ($columns as $key => $value) {
            if (!is_integer($key)) {
                throw new OutOfBoundsException(tr('Specified key ":key" is invalid, keys must be integer', [
                    ':key' => $key,
                ]));
            }

            if (!is_bool($value) and ($value !== null)) {
                throw new OutOfBoundsException(tr('Specified key ":key" has invalid value ":value", values must be boolean', [
                    ':key'   => $key,
                    ':value' => $value,
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     * @todo load javascript libraries only when required, when functionality is enabled
     */
    public function render(): ?string
    {
        // TODO Load many of these javascripts conditionally and only if their functions are enabled (button is there, functionality is required, etc)
        Response::loadJavascript([
            'phoundation/adminlte/plugins/datatables/jquery.dataTables',
            'phoundation/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4',
            'phoundation/adminlte/plugins/datatables-responsive/js/dataTables.responsive',
            'phoundation/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4',
            'phoundation/adminlte/plugins/datatables-buttons/js/dataTables.buttons',
            'phoundation/adminlte/plugins/datatables-buttons/js/buttons.bootstrap4',
            'phoundation/adminlte/plugins/jszip/jszip',
            'phoundation/adminlte/plugins/pdfmake/pdfmake',
            'phoundation/adminlte/plugins/pdfmake/vfs_fonts',
            'phoundation/adminlte/plugins/datatables-buttons/js/buttons.html5',
            'phoundation/adminlte/plugins/datatables-buttons/js/buttons.print',
            'phoundation/adminlte/plugins/datatables-buttons/js/buttons.colVis',
        ]);

        Response::loadCss('phoundation/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4');
        Response::loadCss('phoundation/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4');
        Response::loadCss('phoundation/adminlte/plugins/datatables-buttons/css/buttons.bootstrap4');

        // Build options
        $options = [];
        $content = '';

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

        if (isset($this->buttons) and $this->buttons->isNotEmpty()) {
            $options[] = 'buttons: { buttons: [ ' . implode(', ' . PHP_EOL, array_keys($this->buttons->getSource())) . ' ] }';
        }

        if ($this->responsive) {
            $options[] = $this->getDataTableResponsive();
        }

        if ($this->columns_orderable) {
            $options[] = $this->getDataTableColumnDefinitions();
        }

        if ($this->js_date_format) {
            Response::loadJavascript([
                'phoundation/adminlte/plugins/moment/moment',
                'phoundation/adminlte/plugins/datatables-DateTime-1.5.1/js/dataTables.dateTime',
                'phoundation/adminlte/plugins/datatables-sorting/datetime-moment',
            ]);

            $content .= 'DataTable.moment("' . $this->js_date_format . '")' . PHP_EOL;
        }

        $id = $this->getId();

        if (!$id) {
            if ($this->source) {
                throw new OutOfBoundsException(tr('Cannot generate HTML DataTable, no table id specified'));
            }
        }

        $render = Script::new()
//->setAttach(EnumAttachJavascript::here)
                        ->setJavascriptWrapper(EnumJavascriptWrappers::dom_content)
                        ->setContent($content . '
                $("#' . Html::safe($id) . '").DataTable({
                  ' . implode(', ' . PHP_EOL, $options) . '
                })  .buttons()
                    .container()
                    .appendTo("#' . Html::safe($id) . '_wrapper .col-md-6:eq(0)");')
                        ->render();
//        ' . ($this->date_format ? '.datetime("' . $this->date_format . '")' . PHP_EOL : '') . '
//showdie('$("#' . Html::safe($id) . '").DataTable({
//                  ' . implode(', ' . PHP_EOL, $options) . '
//                }).buttons().container().appendTo("#' . Html::safe($id) . '_wrapper .col-md-6:eq(0)");');

//showdie($render);
        return $render . parent::render();
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
}
