<?php

/**
 * Trait TraitDataDate
 *
 *
 * @see       https://mdbootstrap.com/docs/standard/forms/datepicker/#docsTabsAPI
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Traits;

use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Enums\EnumDatePickerView;


trait TraitDataDate
{
    /**
     * Tracks the date on which the date calendar should start
     *
     * @var PhoDateTimeInterface|null $_start_date
     */
    protected ?PhoDateTimeInterface $_start_date = null;

    /**
     * Tracks the day on which the date calendar should start, 0 for Sunday, 1 for Monday, ... until 6
     *
     * @var int|null $start_day
     */
    protected ?int $start_day = null;

    /**
     * Tracks the calendar title
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * Tracks the minimum allowed date
     *
     * @var PhoDateTimeInterface|null $_minimum_date
     */
    protected ?PhoDateTimeInterface $_minimum_date = null;

    /**
     * Tracks the maximum allowed date
     *
     * @var PhoDateTimeInterface|null $_maximum_date
     */
    protected ?PhoDateTimeInterface $_maximum_date = null;

    /**
     * Tracks if date should be confirmed on select
     *
     * @var bool|null $confirm_date_on_select
     */
    protected ?bool $confirm_date_on_select = null;

    /**
     * Tracks the calendar HTML container
     *
     * @var string|null $html_container
     */
    protected ?string $html_container = null;

    /**
     * Tracks if past dates should be disabled
     *
     * @var bool|null $disable_past
     */
    protected ?bool $disable_past = null;

    /**
     * Tracks if future dates should be disabled
     *
     * @var bool|null $disable_future
     */
    protected ?bool $disable_future = null;

    /**
     * Tracks dates that should be filtered
     *
     * @var string|null $filter_dates
     */
    protected ?string $filter_dates = null;

    /**
     * Tracks custom header template. You can add [day], [month], [weekdayFull], [weekday] and [selected] variables to show proper values in your custom header
     *
     * @var string|null $header_template
     */
    protected ?string $header_template = null;

    /**
     * Tracks to format the Date object in [selected] variable in custom header. Will work only together with headerTemplate
     *
     * @var string|null $header_template_modifier
     */
    protected ?string $header_template_modifier = null;

    /**
     * Tracks date format
     *
     * @var string|null $format
     */
    protected ?string $format = null;

    /**
     * Tracks if the date picker should be inline (dropdown on the text input)
     *
     * @var bool|null $inline
     */
    protected ?bool $inline = null;

    /**
     * Tracks the "Next Month" button label
     *
     * @var string|null $label_next_month
     */
    protected ?string $label_next_month = null;

    /**
     * Tracks the "Previous Month" button label
     *
     * @var string|null $label_previous_month
     */
    protected ?string $label_previous_month = null;

    /**
     * Tracks the "Next Year" button label
     *
     * @var string|null $label_next_year
     */
    protected ?string $label_next_year = null;

    /**
     * Tracks the "Previous Year" button label
     *
     * @var string|null $label_previous_year
     */
    protected ?string $label_previous_year = null;

    /**
     * Tracks the "Next Multi Year" button label
     *
     * @var string|null $label_next_multi_year
     */
    protected ?string $label_next_multi_year = null;

    /**
     * Tracks the "Previous Year" button label
     *
     * @var string|null $label_previous_multi_year
     */
    protected ?string $label_previous_multi_year = null;

    /**
     * Tracks change button aria label in years view
     *
     * @var string|null $label_switch_to_multi_year_view
     */
    protected ?string $label_switch_to_multi_year_view = null;

    /**
     * Tracks change button aria label in days view
     *
     * @var string|null $label_switch_to_day_view
     */
    protected ?string $label_switch_to_day_view = null;

    /**
     * Tracks the visibility of the toggle button: shown when true, hidden when false
     *
     * @var bool|null $enable_toggle_button
     */
    protected ?bool $enable_toggle_button = null;

    /**
     * Tracks default datepicker view (days/years/months)
     *
     * @var EnumDatePickerView|null $default_view
     */
    protected ?EnumDatePickerView $default_view = null;


    /**
     * Returns the date on which the date calendar should start
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStartDateObject(): ?PhoDateTimeInterface
    {
        return $this->_start_date;
    }


    /**
     * Sets the date on which the date calendar should start
     *
     * @param PhoDateTimeInterface|null $date
     *
     * @return static
     */
    public function setStartDateObject(?PhoDateTimeInterface $date): static
    {
        $this->_start_date = $date;
        return $this;
    }


    /**
     * Returns the day on which the date calendar should start, 0 for Sunday, 1 for Monday, ... until 6
     *
     * @return int|null
     */
    public function getStartDay(): ?int
    {
        return $this->start_day;
    }


    /**
     * Sets the day on which the date calendar should start, 0 for Sunday, 1 for Monday, ... until 6
     *
     * @param int|null $day
     *
     * @return static
     */
    public function setStartDay(?int $day): static
    {
        if (($day < 0) or ($day > 6)) {
            throw new OutOfBoundsException(tr('Cannot set start day ":day" on ":class" class ":name", it must be an integer value between 0 - 6', [
                ':day'   => $day,
                ':class' => static::class,
                ':name ' => $this->getName()
            ]));
        }

        $this->start_day = $day;
        return $this;
    }


    /**
     * Returns the calendar title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }


    /**
     * Sets the calendar title
     *
     * @param string|false|null $title            The title for this calendar
     * @param bool              $make_safe [true] If true, will make the title safe for use with HTML
     *
     * @return static
     */
    public function setTitle(string|false|null $title, bool $make_safe = true): static
    {
        $this->title = get_value_unless_false($this->title, $title);
        $this->title = get_null($this->title);

        return $this;
    }


    /**
     * Returns the minimum allowed date
     *
     * @return PhoDateTimeInterface|null
     */
    public function getMinimumDateObject(): ?PhoDateTimeInterface
    {
        return $this->_minimum_date;
    }


    /**
     * Sets the minimum allowed date
     *
     * @param PhoDateTimeInterface|null $date
     *
     * @return static
     */
    public function setMinimumDateObject(?PhoDateTimeInterface $date): static
    {
        if ($this->_maximum_date) {
            if ($date < $this->_maximum_date) {
                throw new OutOfBoundsException(tr('Cannot set minimum date ":date" on ":class" class ":name", it already has a earlier maximum date ":max" set', [
                    ':day'   => $date,
                    ':max'   => $this->_maximum_date,
                    ':class' => static::class,
                    ':name ' => $this->getName()
                ]));
            }
        }

        $this->_minimum_date = $date;
        return $this;
    }


    /**
     * Returns the maximum allowed date
     *
     * @return PhoDateTimeInterface|null
     */
    public function getMaximumDateObject(): ?PhoDateTimeInterface
    {
        return $this->_maximum_date;
    }


    /**
     * Sets the maximum allowed date
     *
     * @param PhoDateTimeInterface|null $date
     *
     * @return static
     */
    public function setMaximumDateObject(?PhoDateTimeInterface $date): static
    {
        if ($this->_minimum_date) {
            if ($date < $this->_minimum_date) {
                throw new OutOfBoundsException(tr('Cannot set minimum date ":date" on ":class" class ":name", it already has a later minimum date ":min" set', [
                    ':day'   => $date,
                    ':min'   => $this->_minimum_date,
                    ':class' => static::class,
                    ':name ' => $this->getName()
                ]));
            }
        }

        $this->_maximum_date = $date;
        return $this;
    }


    /**
     * Returns if date should be confirmed on select
     *
     * @return bool|null
     */
    public function getConfirmDateOnSelect(): ?bool
    {
        return $this->confirm_date_on_select;
    }


    /**
     * Sets if date should be confirmed on select
     *
     * @param bool|null $confirm
     *
     * @return static
     */
    public function setConfirmDateOnSelect(?bool $confirm): static
    {
        $this->confirm_date_on_select = $confirm;
        return $this;
    }


    /**
     * Returns the calendar HTML container
     *
     * @return string|null
     */
    public function getHtmlContainer(): ?string
    {
        return $this->html_container;
    }


    /**
     * Sets the calendar HTML container
     *
     * @param string|null $container
     *
     * @return static
     */
    public function setHtmlContainer(?string $container): static
    {
        $this->html_container = $container;
        return $this;
    }


    /**
     * Returns if past dates should be disabled
     *
     * @return bool|null
     */
    public function getDisablePast(): ?bool
    {
        return $this->disable_past;
    }


    /**
     * Sets if past dates should be disabled
     *
     * @param bool|null $disable
     *
     * @return static
     */
    public function setDisablePast(?bool $disable): static
    {
        $this->disable_past = $disable;
        return $this;
    }


    /**
     * Returns if future dates should be disabled
     *
     * @return bool|null
     */
    public function getDisableFuture(): ?bool
    {
        return $this->disable_future;
    }


    /**
     * Sets if future dates should be disabled
     *
     * @param bool|null $disable
     *
     * @return static
     */
    public function setDisableFuture(?bool $disable): static
    {
        $this->disable_future = $disable;
        return $this;
    }


    /**
     * Returns dates that should be filtered
     *
     * @return string|null
     */
    public function getFilterDates(): ?string
    {
        return $this->filter_dates;
    }


    /**
     * Sets dates that should be filtered
     *
     * @param string|null $dates
     *
     * @return static
     *
     * @todo Confirm that the specified format is a valid PHP or JS format
     */
    public function setFilterDates(?string $dates): static
    {
        $this->filter_dates = $dates;
        return $this;
    }


    /**
     * Returns custom header template. You can add [day], [month], [weekdayFull], [weekday] and [selected] variables to show proper values in your custom header
     *
     * @return string|null
     */
    public function getHeaderTemplate(): ?string
    {
        return $this->header_template;
    }


    /**
     * Sets custom header template. You can add [day], [month], [weekdayFull], [weekday] and [selected] variables to show proper values in your custom header
     *
     * @param string|null $template
     *
     * @return static
     */
    public function setHeaderTemplate(?string $template): static
    {
        $this->header_template = $template;
        return $this;
    }


    /**
     * Returns JavaScript method to format the Date object in [selected] variable in custom header. Will work only together with headerTemplate
     *
     * @return string|null
     */
    public function getHeaderTemplateModifier(): ?string
    {
        return $this->header_template_modifier;
    }


    /**
     * Sets JavaScript method to format the Date object in [selected] variable in custom header. Will work only together with headerTemplate
     *
     * @param string|null $modifier
     *
     * @return static
     */
    public function setHeaderTemplateModifier(?string $modifier): static
    {
        $this->header_template_modifier = $modifier;
        return $this;
    }


    /**
     * Returns date format
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }


    /**
     * Sets date format
     *
     * @param string|null $format
     *
     * @todo Confirm that the specified format is a valid PHP or JS format
     * @return static
     */
    public function setFormat(?string $format): static
    {
        $this->format = $format;
        return $this;
    }


    /**
     * Returns if the date picker should be inline (dropdown on the text input)
     *
     * @return bool|null
     */
    public function getInline(): ?bool
    {
        return $this->inline;
    }


    /**
     * Sets if the date picker should be inline (dropdown on the text input)
     *
     * @param bool|null $inline
     *
     * @return static
     */
    public function setInline(?bool $inline): static
    {
        $this->inline = $inline;
        return $this;
    }


    /**
     * Returns the 'Next Month' button label
     *
     * @return string|null
     */
    public function getLabelNextMonth(): ?string
    {
        return $this->label_next_month;
    }


    /**
     * Sets the 'Next Month' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelNextMonth(?string $label): static
    {
        $this->label_next_month = $label;
        return $this;
    }


    /**
     * Returns the 'Previous Month' button label
     *
     * @return string|null
     */
    public function getLabelPreviousMonth(): ?string
    {
        return $this->label_previous_month;
    }


    /**
     * Sets the 'Previous Month' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelPreviousMonth(?string $label): static
    {
        $this->label_previous_month = $label;
        return $this;
    }


    /**
     * Returns the 'Next Year' button label
     *
     * @return string|null
     */
    public function getLabelNextYear(): ?string
    {
        return $this->label_next_year;
    }


    /**
     * Sets the 'Next Year' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelNextYear(?string $label): static
    {
        $this->label_next_year = $label;
        return $this;
    }


    /**
     * Returns the 'Previous Year' button label
     *
     * @return string|null
     */
    public function getLabelPreviousYear(): ?string
    {
        return $this->label_previous_year;
    }


    /**
     * Sets the 'Previous Year' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelPreviousYear(?string $label): static
    {
        $this->label_previous_year = $label;
        return $this;
    }


    /**
     * Returns the 'Next Multi Year' button label
     *
     * @return string|null
     */
    public function getLabelNextMultiYear(): ?string
    {
        return $this->label_next_multi_year;
    }


    /**
     * Sets the 'Next Multi Year' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelNextMultiYear(?string $label): static
    {
        $this->label_next_multi_year = $label;
        return $this;
    }


    /**
     * Returns the 'Previous Multi Year' button label
     *
     * @return string|null
     */
    public function getLabelPreviousMultiYear(): ?string
    {
        return $this->label_previous_multi_year;
    }


    /**
     * Sets the 'Previous Multi Year' button label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelPreviousMultiYear(?string $label): static
    {
        $this->label_previous_multi_year = $label;
        return $this;
    }


    /**
     * Returns change button aria label in years view
     *
     * @return string|null
     */
    public function getLabelSwitchToMultiYearView(): ?string
    {
        return $this->label_switch_to_multi_year_view;
    }


    /**
     * Sets change button aria label in years view
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelSwitchToMultiYearView(?string $label): static
    {
        $this->label_switch_to_multi_year_view = $label;
        return $this;
    }


    /**
     * Returns change button aria label in days view
     *
     * @return string|null
     */
    public function getLabelSwitchToDayView(): ?string
    {
        return $this->label_switch_to_day_view;
    }


    /**
     * Sets change button aria label in days view
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabelSwitchToDayView(?string $label): static
    {
        $this->label_switch_to_day_view = $label;
        return $this;
    }


    /**
     * Returns the visibility of the toggle button: shown when true, hidden when false
     *
     * @return string|null
     */
    public function getEnableToggleButton(): ?string
    {
        return $this->enable_toggle_button;
    }


    /**
     * Sets the visibility of the toggle button: shown when true, hidden when false
     *
     * @param string|null $enable
     *
     * @return static
     */
    public function setEnableToggleButton(?string $enable): static
    {
        $this->enable_toggle_button = $enable;
        return $this;
    }


    /**
     * Returns default datepicker view (days/years/months)
     *
     * @return EnumDatePickerView|null
     */
    public function getDefaultView(): ?EnumDatePickerView
    {
        return $this->default_view;
    }


    /**
     * Sets default datepicker view (days/years/months)
     *
     * @param EnumDatePickerView|null $default_view
     *
     * @return static
     */
    public function setDefaultView(?EnumDatePickerView $default_view): static
    {
        $this->default_view = $default_view;
        return $this;
    }


    /**
     * Renders an "options" array for MDB datepicker
     *
     * @return array|null
     */
    public function renderOptions(bool $strip_object_brackets = true): ?string
    {
        if ($strip_object_brackets) {
            return substr(Json::encode($this->renderOptionsArray()), 1, -1);
        }

        return Json::encode($this->renderOptionsArray());
    }


    /**
     * Renders an "options" array for MDB datepicker
     *
     * @return array|null
     */
    public function renderOptionsArray(): ?array
    {
        $options = [
            'animations'      => config()->getBoolean('web.display.animations.date', true),
            'cancelBtnLabel'  => tr('Cancel'),
            'cancelBtnText'   => tr('Cancel'),
            'removeCancelBtn' => false,

            'clearBtnLabel'  => tr('Today'),
            'clearBtnText'   => tr('Today'),
            'removeClearBtn' => false,

            'okBtnLabel'  => tr('Ok'),
            'okBtnText'   => tr('Ok'),
            'removeOkBtn' => false,

            'monthsFull' => [
                tr('January'),
                tr('February'),
                tr('March'),
                tr('April'),
                tr('May'),
                tr('June'),
                tr('July'),
                tr('August'),
                tr('September'),
                tr('October'),
                tr('November'),
                tr('December')
            ],

            'monthsShort' => [
                tr('Jan'),
                tr('Feb'),
                tr('Mar'),
                tr('Apr'),
                tr('May'),
                tr('Jun'),
                tr('Jul'),
                tr('Aug'),
                tr('Sep'),
                tr('Oct'),
                tr('Nov'),
                tr('Dec')
            ],

            'weekdaysFull' => [
                tr('Sunday'),
                tr('Monday'),
                tr('Tuesday'),
                tr('Wednesday'),
                tr('Thursday'),
                tr('Friday'),
                tr('Saturday')
            ],

            'weekdaysNarrow' => [
                tr('S'),
                tr('M'),
                tr('T'),
                tr('W'),
                tr('T'),
                tr('F'),
                tr('S')
            ],

            'weekdaysShort' => [
                tr('Sun'),
                tr('Mon'),
                tr('Tue'),
                tr('Wed'),
                tr('Thu'),
                tr('Fri'),
                tr('Sat')
            ],
        ];

        // Add optional options
        if ($this->getStartDateObject()) {
            $options['startDate'] = $this->getStartDateObject()
                                          ->format('javascript');
        }

        if ($this->getStartDay()) {
            $options['startDay'] = $this->getStartDay();
        }

        if ($this->getTitle()) {
            $options['title'] = $this->getTitle();
        }

        if ($this->getMinimumDateObject()) {
            $options['min'] = $this->getMinimumDateObject()->format(EnumDateFormat::mysql_datetime);
        }

        if ($this->getMaximumDateObject()) {
            $options['max'] = $this->getMaximumDateObject()->format(EnumDateFormat::mysql_datetime);
        }

        if ($this->getConfirmDateOnSelect()) {
            $options['confirmDateOnSelect'] = $this->getConfirmDateOnSelect();
        }

        if ($this->getHtmlContainer()) {
            $options['container'] = $this->getHtmlContainer();
        }

        if ($this->getDisablePast()) {
            $options['disablePast'] = $this->getDisablePast();
        }

        if ($this->getDisableFuture()) {
            $options['disableFuture'] = $this->getDisableFuture();
        }

        if ($this->getFilterDates()) {
            $options['filter'] = $this->getFilterDates();
        }

        if ($this->getFormat()) {
            $options['format'] = $this->getFormat();
        }

        if ($this->getHeaderTemplate()) {
            $options['headerTemplate'] = $this->getHeaderTemplate();
        }

        if ($this->getHeaderTemplateModifier()) {
            $options['headerTemplateModifier'] = $this->getHeaderTemplateModifier();
        }

        if ($this->getInline()) {
            $options['inline'] = $this->getInline();
        }

        if ($this->getLabelNextMonth()) {
            $options['nextMonthLabel'] = $this->getLabelNextMonth();
        }

        if ($this->getLabelPreviousMonth()) {
            $options['prevMonthLabel'] = $this->getLabelPreviousMonth();
        }

        if ($this->getLabelNextMultiYear()) {
            $options['nextMultiYearLabel'] = $this->getLabelNextMultiYear();
        }

        if ($this->getLabelPreviousMultiYear()) {
            $options['prevMultiYearLabel'] = $this->getLabelPreviousMultiYear();
        }

        if ($this->getLabelNextYear()) {
            $options['nextYearLabel'] = $this->getLabelNextYear();
        }

        if ($this->getLabelPreviousYear()) {
            $options['prevYearLabel'] = $this->getLabelPreviousYear();
        }

        if ($this->getLabelSwitchToDayView()) {
            $options['switchToDayViewLabel'] = $this->getLabelSwitchToDayView();
        }

        if ($this->getLabelSwitchToMultiYearView()) {
            $options['switchToMultiYearViewLabel'] = $this->getLabelSwitchToMultiYearView();
        }

        if ($this->getEnableToggleButton()) {
            $options['toggleButton'] = $this->getEnableToggleButton();
        }

        if ($this->getDefaultView()) {
            $options['view'] = $this->getDefaultView()->value;
        }

        return $options;
    }
}
