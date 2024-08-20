<?php

/**
 * Class InputDateRange
 *
 * Standard HTML date range input control
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\TraitDataDateFormat;
use Phoundation\Data\Traits\TraitDataStartDate;
use Phoundation\Data\Traits\TraitDataStopDate;
use Phoundation\Date\DateFormats;
use Phoundation\Date\DateRangePickerRanges;
use Phoundation\Date\Interfaces\DateRangePickerRangesInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Requests\Response;


class InputDateRange extends InputText
{
    use TraitDataStartDate;
    use TraitDataStopDate;
    use TraitDataDateFormat;


    /**
     * The HTML selector to which the daterange will respond
     *
     * @var string|null $parent_selector
     */
    protected ?string $parent_selector = null;

    /**
     * Date ranges
     *
     * @var DateRangePickerRangesInterface $ranges
     */
    protected DateRangePickerRangesInterface $ranges;


    /**
     * InputDateRange class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setFormat(DateFormats::getDefaultJavascript())
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
     * @return DateRangePickerRangesInterface
     */
    public function getRanges(): DateRangePickerRangesInterface
    {
        if (empty($this->ranges)) {
            $this->ranges = new DateRangePickerRanges();
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
        Response::loadJavascript('Phoundation/adminlte/plugins/moment/moment');
        Response::loadJavascript('Phoundation/adminlte/plugins/daterangepicker/daterangepicker');
        Response::loadJavascript('Phoundation/adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');

        // Required CSS
        Response::loadCss('Phoundation/adminlte/plugins/daterangepicker/daterangepicker');
        Response::loadCss('Phoundation/adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4');

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
                    ' . $this->renderRanges() . '
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
     * Builds the ranges string, if any
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
