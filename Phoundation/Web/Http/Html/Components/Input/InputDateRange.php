<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Date\DateRangePickerRanges;
use Phoundation\Date\Interfaces\DateRangePickerRangesInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Page;
use Plugins\Medinet\Traits\DataStartDate;
use Plugins\Medinet\Traits\DataStopDate;


/**
 * Class InputDateRange
 *
 * Standard HTML date range input control
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDateRange extends InputText
{
    use DataStartDate;
    use DataStopDate;


    /**
     * The HTML selector to which the daterange will respond
     *
     * @var string|null $parent_selector
     */
    protected ?string $parent_selector = null;

    /**
     * The auto submit code for this control
     *
     * @var string|null $auto_submit
     */
    protected ?string $auto_submit = null;

    /**
     * Date ranges
     *
     * @var DateRangePickerRangesInterface $ranges
     */
    protected DateRangePickerRangesInterface $ranges;


    /**
     * InputDateRange class constructor
     */
    public function __construct()
    {
        $this->type = InputType::text;
        parent::__construct();
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
     * @return $this
     */
    public function setParentSelector(?string $selector): static
    {
        $this->parent_selector = $selector;
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
     * Specify what pre-programmed range to use
     *
     * @param string $range
     * @return $this
     */
    public function useRange(string $range): static
    {
        switch ($range) {
            case 'default':
                $this->getRanges()->useDefault();
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown range ":range" specified, specify one of "default"', [
                    ':range' => $range
                ]));
        }

        return $this;
    }


    /**
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Required javascript
        Page::loadJavascript('adminlte/plugins/moment/moment');
        Page::loadJavascript('adminlte/plugins/daterangepicker/daterangepicker');
        Page::loadJavascript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');

        // Required CSS
        Page::loadCss('adminlte/plugins/daterangepicker/daterangepicker');
        Page::loadCss('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4');

        // Setup & configuration script for daterangepicker
        Script::new()
            ->setJavascriptWrapper(JavascriptWrappers::window)
            ->setContent('
                $("#' . $this->getId() . '").daterangepicker(
                {
                    onSelect: function(dateText, inst) {
                        return $(this).trigger("change");
                    },
                    parentEl: "' . $this->parent_selector . '",
                    ' . $this->buildRanges() . '
//                    startDate: moment().subtract(29, "days"),
//                    endDate  : moment()
                },
                function (start, end) {
//                  $("#' . $this->getId() . '").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"))
                });')
            ->render();

        return parent::render();
    }


    /**
     * Builds the ranges string, if any
     *
     * @return string|null
     */
    protected function buildRanges(): ?string
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
