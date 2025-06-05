<?php

/**
 * Class InputDate
 *
 * @see       https://www.daterangepicker.com/
 * @see       https://getdatepicker.com/
 * @see       https://flatpickr.js.org/
 * @see       https://github.com/eureka2/ab-datepicker
 * @see       https://datebox.jtsage.dev/
 * @see       https://preview.keenthemes.com/html/metronic/docs/forms/daterangepicker
 * @see       https://mdbootstrap.com/docs/standard/forms/datepicker/#docsTabsAPI
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Traits\TraitDataDate;
use Stringable;


class InputDate extends InputText
{
    use TraitDataDate;


    /**
     * InputDate class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumInputType::date;

        parent::__construct($content);

        $this->setConfirmDateOnSelect(true)
             ->setFormat(Session::getLocaleObject()->getDateFormatJavascript(true));
    }


    /**
     * Sets the value for the input element
     *
     * @param PhoDateTimeInterface|Stringable|string|float|int|null $value
     * @param bool                                         $make_safe
     *
     * @return static
     */
    public function setValue(PhoDateTimeInterface|Stringable|string|float|int|null $value, bool $make_safe = true): static
    {

        if ($value instanceof PhoDateTimeInterface) {
            $value = $value->format(Session::getLocaleObject()->getDateFormatPhp());

        } else {
            $value = PhoDateTime::new($value)->format(EnumDateFormat::user_date);
        }

        return parent::setValue($value, $make_safe);
    }


    /**
     * Returns the maximum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMax(): ?int
    {
        return $this->o_attributes->get('max', false);
    }


    /**
     * Sets the maximum numeric value for this numeric input
     *
     * @param PhoDateTimeInterface|Stringable|string|null $max
     *
     * @return static
     */
    public function setMax(PhoDateTimeInterface|Stringable|string|null $max): static
    {
        if ($max instanceof PhoDateTimeInterface) {
            $max = $max->format('Y-m-d');
        }

        return $this->setAttribute(get_null((string) $max), 'max');
    }


    /**
     * Returns the minimum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMin(): ?int
    {
        return $this->o_attributes->get('min', false);
    }


    /**
     * Sets the minimum date for this numeric input
     *
     * @param PhoDateTimeInterface|Stringable|string|null $min
     *
     * @return static
     */
    public function setMin(PhoDateTimeInterface|Stringable|string|null $min): static
    {
        if ($min instanceof PhoDateTimeInterface) {
            $min = $min->format('Y-m-d');
        }

        return $this->setAttribute(get_null((string) $min), 'min');
    }


    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $o_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $o_definition): static
    {
        // Copy data used for input controls
        return parent::setDefinitionObject($o_definition)
                     ->setMinimumDateObject($o_definition->getMinimumDateObject())
                     ->setMaximumDateObject($o_definition->getMaximumDateObject());
    }
}
