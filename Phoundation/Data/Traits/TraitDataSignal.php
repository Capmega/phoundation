<?php

/**
 * Trait TraitDataSignal
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Os\Enums\EnumSignal;
use Phoundation\Os\Processes\Signals;


trait TraitDataSignal
{
    /**
     * The signal for this object
     *
     * @var EnumSignal|null $signal
     */
    protected ?EnumSignal $signal = null;


    /**
     * Returns the signal
     *
     * @return EnumSignal|null
     */
    public function getSignal(): ?EnumSignal
    {
        return $this->signal;
    }


    /**
     * Sets the signal
     *
     * @param EnumSignal|null $signal
     *
     * @return static
     */
    public function setSignal(?EnumSignal $signal): static
    {
        $this->signal = Signals::check($signal);
        return $this;
    }
}
