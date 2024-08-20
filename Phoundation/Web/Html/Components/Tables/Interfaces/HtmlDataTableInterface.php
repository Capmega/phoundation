<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Tables\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Enums\EnumPagingType;

interface HtmlDataTableInterface extends HtmlTableInterface
{
    /**
     * Returns table top-buttons
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface;


    /**
     * Sets table top-buttons
     *
     * @param ButtonsInterface|array|string|null $buttons
     *
     * @return static
     */
    public function setButtons(ButtonsInterface|array|string|null $buttons): static;


    /**
     * Returns if responsive table is enabled or not
     *
     * @return bool|null
     */
    public function getResponsiveEnabled(): ?bool;


    /**
     * Sets if responsive table is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setResponsiveEnabled(?bool $enabled): static;


    /**
     * Returns responsive breakpoints
     *
     * @return array|null
     */
    public function getResponsiveBreakpoints(): ?array;


    /**
     * Sets responsive breakpoints
     *
     * @param array|null $breakpoints
     *
     * @return static
     */
    public function setResponsiveBreakpoints(?array $breakpoints): static;


    /**
     * Returns if table information display field is enabled or not
     *
     * @return bool|null
     */
    public function getInfoEnabled(): ?bool;


    /**
     * Sets if table information display field is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setInfoEnabled(?bool $enabled): static;


    /**
     * Returns if search (filtering) abilities are enabled or disabled
     *
     * @return bool|null
     */
    public function getSearchingEnabled(): ?bool;


    /**
     * Sets if search (filtering) abilities are enabled or disabled
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setSearchingEnabled(?bool $enabled): static;


    /**
     * Returns if case-sensitive filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchCaseInsensitiveEnabled(): ?bool;


    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setSearchCaseInsensitiveEnabled(?bool $enabled): static;


    /**
     * Returns if escaping of regular expression characters in the search term is enabled or not
     *
     * @return bool|null
     */
    public function getSearchRegexEnabled(): ?bool;


    /**
     * Sets if escaping of regular expression characters in the search term is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setSearchRegexEnabled(?bool $enabled): static;


    /**
     * Returns if DataTables' smart filtering is enabled or not
     *
     * @return bool|null
     */
    public function getSearchSmartEnabled(): ?bool;


    /**
     * Sets if DataTables' smart filtering is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setSearchSmartEnabled(?bool $enabled): static;


    /**
     * Returns if search on return is enabled or not
     *
     * @return bool|null
     */
    public function getSearchReturnEnabled(): ?bool;


    /**
     * Sets if search on return is enabled or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setSearchReturnEnabled(?bool $enabled): static;


    /**
     * Returns the initial filtering condition on the table
     *
     * @return string
     */
    public function getSearch(): string;


    /**
     * Sets the initial filtering condition on the table
     *
     * @param string|null $search
     *
     * @return static
     */
    public function setSearch(?string $search): static;


    /**
     * Returns if paging is enabled or disabled
     *
     * @return bool|null
     */
    public function getPagingEnabled(): ?bool;


    /**
     * Sets if paging is enabled or disabled
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setPagingEnabled(?bool $enabled): static;


    /**
     * Sets pagination button display options
     *
     * @return EnumPagingType
     */
    public function getPagingType(): EnumPagingType;


    /**
     * Sets pagination button display options
     *
     * @param EnumPagingType $type
     *
     * @return static
     */
    public function setPagingType(EnumPagingType $type): static;


    /**
     * Returns the menu available to the user displaying the optional paging lengths
     *
     * @return array
     */
    public function getLengthMenu(): array;


    /**
     * Sets the menu available to the user displaying the optional paging lengths
     *
     * @param array|null $length_menu
     *
     * @return static
     */
    public function setLengthMenu(?array $length_menu): static;


    /**
     * Returns if the length menu is displayed or not
     *
     * @return bool|null
     */
    public function getLengthChangeEnabled(): ?bool;


    /**
     * Sets if the length menu is displayed or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setLengthChangeEnabled(?bool $enabled): static;


    /**
     * Returns the default page length
     *
     * @return int
     */
    public function getPageLength(): int;


    /**
     * Sets the default page length
     *
     * @param int $length
     *
     * @return static
     */
    public function setPageLength(int $length): static;


    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @return bool|null
     */
    public function getAutoWidthEnabled(): ?bool;


    /**
     * Sets the feature control DataTables' smart column width handling
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setAutoWidthEnabled(?bool $enabled): static;


    /**
     * Returns if deferred rendering for additional speed of initialisation is used
     *
     * @return bool|null
     */
    public function getDeferRenderEnabled(): ?bool;


    /**
     * Sets if deferred rendering for additional speed of initialisation is used
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setDeferRenderEnabled(?bool $enabled): static;


    /**
     * Returns initial paging start point.
     *
     * @return int
     */
    public function getDisplayStart(): int;


    /**
     * Sets initial paging start point.
     *
     * @param int $start
     *
     * @return static
     */
    public function setDisplayStart(int $start): static;


    /**
     * Returns initial order (sort) to apply to the table
     *
     * @return array
     */
    public function getOrder(): array;


    /**
     * Sets initial order (sort) to apply to the table
     *
     * @param array|null $order
     *
     * @return static
     */
    public function setOrder(?array $order): static;


    /**
     * Returns ordering to always be applied to the table
     *
     * @return array
     */
    public function getOrderFixed(): array;


    /**
     * Sets ordering to always be applied to the table
     *
     * @param array|null $order
     *
     * @return static
     */
    public function setOrderFixed(?array $order): static;


    /**
     * Returns the columns that can be ordered
     *
     * @return array
     */
    public function getColumnsOrderable(): array;


    /**
     * Sets the columns that can be ordered
     *
     * @param array|null $columns
     *
     * @return static
     */
    public function setColumnsOrderable(?array $columns): static;


    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @return bool|null
     */
    public function getOrderingEnabled(): ?bool;


    /**
     * Sets if ordering (sorting) abilities are available in DataTables
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setOrderingEnabled(?bool $enabled): static;


    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @return bool|null
     */
    public function getOrderClassesEnabled(): ?bool;


    /**
     * Sets if the columns being ordered in the table's body is highlighted
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setOrderClassesEnabled(?bool $enabled): static;


    /**
     * Returns if multiple column ordering ability is available or not
     *
     * @return bool|null
     */
    public function getOrderMultiEnabled(): ?bool;


    /**
     * Sets if multiple column ordering ability is available or not
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setOrderMultiEnabled(?bool $enabled): static;


    /**
     * Generates and returns the HTML string for this resource element
     *
     * @return string|null
     */
    public function render(): ?string;


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
    public function getJsDateFormat(): ?string;


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
     * Hour                 H                   0 1 ... 22 23
     *                      HH                  00 01 ... 22 23
     *                      h                   1 2 ... 11 12
     *                      hh                  01 02 ... 11 12
     *                      k                   1 2 ... 23 24
     *                      kk                  01 02 ... 23 24
     * Minute               m                   0 1 ... 58 59
     *                      mm                  00 01 ... 58 59
     * Second               s                   0 1 ... 58 59
     *                      ss                  00 01 ... 58 59
     * Fractional Second    S                   0 1 ... 8 9
     *                      SS                  00 01 ... 98 99
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
    public function setJsDateFormat(?string $date_format): static;
}
