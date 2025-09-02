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
             ->setFormat(Session::getLocaleObject()->getDateFormatJavaScript(true));
    }


    /**
     * Sets the value for the input element
     *
     * @param PhoDateTimeInterface|Stringable|string|float|int|null $value
     * @param bool                                         $make_safe
     *
     * @return static
     */
    public function setValue(PhoDateTimeInterface|Stringable|string|float|int|null $value, bool $make_safe = false): static
    {
        if ($value instanceof PhoDateTimeInterface) {
            $value = $value->format($this->getDateFormat(Session::getLocaleObject()->getDateFormatPhp()));

        } else {
            $value = PhoDateTime::new($value)->format($this->getDateFormat());
        }

        return parent::setValue($value, $make_safe);
    }


    /**
     * Returns the date format required by the used template for this InputDate class
     *
     * @param EnumDateFormat|string $requested_format
     *
     * @return EnumDateFormat|string
     */
    public function getDateFormat(EnumDateFormat|string $requested_format = EnumDateFormat::user_date): EnumDateFormat|string
    {
        $class = static::getRenderClass(true);

        if ($class) {
            return $class::getDateFormat() ?? $requested_format;
        }

        return $requested_format;

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
     * Returns the DataEntry Definition on this element
     *
     * If no Definition object was set, one will be created using the data in this object
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface
    {
        // Copy data used for input controls
        return parent::getDefinitionObject()
                     ->setMinimumDateObject($this->getMinimumDateObject())
                     ->setMaximumDateObject($this->getMaximumDateObject());
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
