<?php

namespace Phoundation\Geo\Countries\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;
use Phoundation\Web\Html\Components\Input\InputSelect;

interface CountryInterface extends DataEntryInterface
{
    /**
     * Returns the general timezone for this country
     *
     * @return TimezoneInterface
     */
    public function getTimezoneObject(): TimezoneInterface;

    /**
     * Returns the continent for this country
     *
     * @return ContinentInterface
     */
    public function getContinentObject(): ContinentInterface;

    /**
     * Returns an HTML <select> object with all states available in this country
     *
     * @param string $name
     *
     * @return InputSelect
     */
    public function getHtmlStatesSelect(string $name = 'states_id'): InputSelect;
}
