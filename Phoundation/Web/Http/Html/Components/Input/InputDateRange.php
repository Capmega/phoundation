<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\InputType;
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
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        Script::new()
            ->setEventWrapper('dom_content')
            ->setContent('
                $("#' . $this->getId() . '").daterangepicker(
                {
                    onSelect: function(dateText, inst) {
                        return $(this).trigger("change");
                    },
                    parentEl: "' . $this->parent_selector . '",
                    ranges   : {
                        "Today"  : [moment(), moment()],
                        "Yesterday"   : [moment().subtract(1, "days"), moment().subtract(1, "days")],
                        "Last 7 Days" : [moment().subtract(6, "days"), moment()],
                        "Last 30 Days": [moment().subtract(29, "days"), moment()],
                        "This Month"  : [moment().startOf("month"), moment().endOf("month")],
                        "Last Month"  : [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
                        "This Year"   : [moment().startOf("year"), moment().endOf("year")],
                        "Last Year"   : [moment().subtract(1, "year").startOf("year"), moment().subtract(1, "year").endOf("year")]
                    }
//                    startDate: moment().subtract(29, "days"),
//                    endDate  : moment()
                },
                function (start, end) {
//                  $("#' . $this->getId() . '").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"))
                });')
            ->render();

        return parent::render();
    }
}