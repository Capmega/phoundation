<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;


/**
 * Interface DateRangePickerRangesInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */
interface DateRangePickerRangesInterface extends IteratorInterface
{
    /**
     * Set the default daterangepicker ranges
     *
     * @return $this
     */
    public function useDefault(): static;
}
