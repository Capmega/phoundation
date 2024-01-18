<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Enums\EnumMatchMode;
use Phoundation\Utils\Enums\Interfaces\EnumMatchModeInterface;
use Stringable;
use Throwable;
use UnitEnum;


/**
 * Class Utils
 *
 * This is the standard Utils class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Utils
 */
class Utils {
    const MATCH_ALL      = 1;
    const MATCH_ANY      = 2;
    const MATCH_BEGIN    = 4;
    const MATCH_END      = 8;
    const MATCH_ANYWHERE = 16;
    const MATCH_NO_CASE  = 32;
    const MATCH_RECURSE  = 64;


    /**
     * If set, will filter NULL values
     */
    const FILTER_NULL = 1;

    /**
     * If set, will filter all empty values
     */
    const FILTER_EMPTY = 2;

    /**
     * If set, will quote all values
     */
    const QUOTE_ALWAYS = 4;

    /**
     * If set, will only display key, not value
     */
    const HIDE_EMPTY_VALUES = 8;


    /**
     * Decodes and checks the match flags and returns array with all match options
     *
     * @param int $options
     * @return array
     */
    protected static function decodeMatchOptions(int $options, bool $allow_recurse): array
    {
        // Decode options
        $return                   = [];
        $return['match_no_case']  = (bool) ($options & self::MATCH_NO_CASE);
        $return['match_all']      = (bool) ($options & self::MATCH_ALL);
        $return['match_any']      = (bool) ($options & self::MATCH_ANY);
        $return['match_begin']    = (bool) ($options & self::MATCH_BEGIN);
        $return['match_end']      = (bool) ($options & self::MATCH_END);
        $return['match_anywhere'] = (bool) ($options & self::MATCH_ANYWHERE);
        $return['recurse']        = (bool) ($options & self::MATCH_RECURSE);

        // Validate options
        if ($return['match_begin']) {
            if ($return['match_end'] or $return['match_anywhere']) {
                throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_BEGIN with MATCH_END or MATCH_ANYWHERE, they are mutually exclusive'));
            }

        } else {
            if ($return['match_end'] and $return['match_anywhere']) {
                throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_END with MATCH_ANYWHERE, they are mutually exclusive'));
            }

            if (!$return['match_end'] and !$return['match_anywhere']) {
                throw new OutOfBoundsException(tr('No match location flag specified. One of MATCH_BEGIN, MATCH_END, or MATCH_ANYWHERE must be specified'));
            }
        }

        if ($return['match_all']) {
            if ($return['match_any']) {
                throw new OutOfBoundsException(tr('Cannot mix combination flags MATCH_ALL with MATCH_ANY, they are mutually exclusive'));
            }

        } else {
            if (!$return['match_any']) {
                throw new OutOfBoundsException(tr('No match combination flag specified. Either one of MATCH_ALL or MATCH_ANY must be specified'));
            }
        }

        if ($return['recurse'] and !$allow_recurse) {
            throw new OutOfBoundsException(tr('Recursion matching not allowed'));
        }

        return $return;
    }


    /**
     * Checks specified needles that they have content and will ensure they are specified as an array
     *
     * @param array|Stringable|string $needles
     * @param bool $lowercase
     * @return array
     */
    protected static function checkRequiredNeedles(array|Stringable|string $needles, bool $lowercase = false): array
    {
        if (!$needles) {
            throw new OutOfBoundsException(tr('No needles specified'));
        }

        $needles = Arrays::force($needles);

        if ($lowercase) {
            // Make all needles lowercase strings
            foreach ($needles as &$needle) {
                $needle = strtolower((string) $needle);
            }

            unset($needle);
        }

        return $needles;
    }


    /**
     * Returns the test value from the specified haystack
     *
     * Caseless compares always compare lowercase
     *
     * @param string|null $haystack
     * @param bool $match_no_case
     * @return string
     */
    protected static function getTestValue(?string $haystack, bool $match_no_case): string
    {
        if ($match_no_case) {
            return strtolower((string) $haystack);
        }

        return (string) $haystack;
    }


    /**
     * Returns true if the given haystack matches the given needles with the specified match flags
     *
     * @param Stringable|string $haystack
     * @param array $needles
     * @param array $flags
     * @return bool
     */
    protected static function testStringMatchesNeedles(Stringable|string $haystack, array $needles, array $flags): bool
    {
        // Compare to each needle
        foreach ($needles as $needle) {
            if ($flags['match_begin']) {
                if (str_starts_with($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['match_any']) {
                        return true;
                    }

                    continue;
                }

            } elseif ($flags['match_end']) {
                if (str_ends_with($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['match_any']) {
                        return true;
                    }

                    continue;
                }

            } else {
                if (str_contains($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['match_any']) {
                        return true;
                    }

                    continue;
                }
            }

            // This needle failed to match
            if ($flags['match_all']) {
                return false;
            }
        }

        return true;
    }
}
