<?php

/**
 * Class Signals
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Enums\EnumSignal;


class Signals
{
    /**
     * Throws an exception if the specified signal does not exist
     *
     * @param EnumSignal|int|null $signal
     *
     * @return int|null
     */
    public static function check(EnumSignal|int|null $signal): ?int
    {
        if ($signal instanceof EnumSignal) {
            $signal = $signal->value;
        }

        if (!static::exists($signal)) {
            throw new OutOfBoundsException(tr('The specified signal ":signal" does not exist', [
                ':signal' => $signal,
            ]));
        }

        return $signal;
    }


    /**
     * Returns true if the specified signal exists
     *
     * @param int|null $signal
     *
     * @return bool
     */
    public static function exists(?int $signal): bool
    {
        if ($signal === null) {
            return true;
        }

        return EnumSignal::tryFrom($signal) !== null;
    }


    /**
     * Returns a list of all known process signals
     *
     * @return string[]
     */
    public static function get(): array
    {
        return array_column(EnumSignal::cases(), 'name');;
    }
}
