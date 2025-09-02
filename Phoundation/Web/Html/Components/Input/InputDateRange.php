<?php

/**
 * Class InputDateRange
 *
 * Standard HTML date range input control
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\TraitDataStartDate;
use Phoundation\Data\Traits\TraitDataStopDate;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Enums\EnumDateTimeWidth;
use Phoundation\Date\PhoDateTimeFormats;
use Phoundation\Date\PhoDateRangePickerRanges;
use Phoundation\Date\Interfaces\PhoDateRangePickerRangesInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Traits\TraitDataDate;


class InputDateRange extends InputText
{
    use TraitDataDate;
    use TraitDataStartDate;
    use TraitDataStopDate;


    /**
     * The HTML selector to which the date range will respond
     *
     * @var string|null $parent_selector
     */
    protected ?string $parent_selector = null;

    /**
     * Date ranges
     *
     * @var PhoDateRangePickerRangesInterface $ranges
     */
    protected PhoDateRangePickerRangesInterface $ranges;


    /**
     * InputDateRange class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setFormat(PhoDateTimeFormats::getDefaultDateFormatJavaScript(width: EnumDateTimeWidth::normal))
             ->input_type = EnumInputType::text;

        parent::__construct($content);
    }


    /**
     * Returns the date range mounting id
     *
     * @return string|null
     */
    public function getParentSelector(): ?string
    {
        return $this->parent_selector;
    }


    /**
     * Sets the date range mounting selector
     *
     * @param string|null $selector
     *
     * @return static
     */
    public function setParentSelector(?string $selector): static
    {
        $this->parent_selector = $selector;
        return $this;
    }


    /**
     * Specify what pre-programmed ranges to use
     *
     * @param string $ranges
     *
     * @return static
     */
    public function useRanges(string $ranges): static
    {
        switch ($ranges) {
            case 'default':
                $this->getRanges()->useDefault();
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown ranges ":ranges" specified, specify one of "default"', [
                    ':ranges' => $ranges,
                ]));
        }

        return $this;
    }


    /**
     * Returns the date ranges object
     *
     * @return PhoDateRangePickerRangesInterface
     */
    public function getRanges(): PhoDateRangePickerRangesInterface
    {
        if (empty($this->ranges)) {
            $this->ranges = new PhoDateRangePickerRanges();
        }

        return $this->ranges;
    }


    /**
     * Render and return the HTML for this Input Element
     *
     * @see https://daterangepicker.com/#config
     * @return string|null
     */
    public function render(): ?string
    {
        // Required javascript
        Response::loadJavaScript('adminlte/plugins/moment/moment');
        Response::loadJavaScript('adminlte/plugins/daterangepicker/daterangepicker');
        Response::loadJavaScript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');

        // Required CSS
        Response::loadCss('adminlte/plugins/daterangepicker/daterangepicker');
        Response::loadCss('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4');

        // Setup & configuration script for daterangepicker
        Script::new()
              ->setJavascriptWrapper(EnumJavascriptWrappers::window)
              ->setContent('
                $("[name=' . $this->getName() . ']").daterangepicker(
                {
                    locale: {
                        format: "' . $this->format . '"
                    },                                
                    onSelect: function(dateText, inst) {
                        return $(this).trigger("change");
                    },
                    parentEl: "' . $this->parent_selector . '",
                    ' . $this->renderRanges() . ',
                      ' . ($this->getMinimumDateObject() ? 'minDate: "' . $this->getMinimumDateObject()->format(EnumDateFormat::human_date) . '",' : null) . '
                      ' . ($this->getMaximumDateObject() ? 'maxDate: "' . $this->getMaximumDateObject()->format(EnumDateFormat::human_date) . '",' : null) . '
//                    startDate: moment().subtract(29, "days"),
//                    endDate  : moment()
                },
                function (start, end) {
//                  $("[name=' . $this->getName() . ']").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"))
                });')
              ->render();

        return parent::render();
    }


    /**
     * Builds and returns the "ranges" string, if any
     *
     * @return string|null
     */
    protected function renderRanges(): ?string
    {
        if (empty($this->ranges) or $this->ranges->isEmpty()) {
            return null;
        }

        $return = [];

        foreach ($this->ranges as $key => $range) {
            $return[] = '"' . $key . '"  : ' . $range;
        }

        return 'ranges : {' . implode(',' . PHP_EOL, $return) . '}' . PHP_EOL;
    }
}
