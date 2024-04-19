<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;

/**
 * Class Utils
 *
 * This is the standard Utils class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */
class Utils
{
    /**
     * Match options
     */
    const MATCH_NOT              = 1;

    const MATCH_ANY              = 2;

    const MATCH_ALL              = 4;
    const MATCH_REQUIRE          = 8;
    const MATCH_STRICT           = 16;
    const MATCH_STARTS_WITH      = 32;

    const MATCH_ENDS_WITH        = 64;
    const MATCH_CONTAINS         = 128;
    const MATCH_FULL             = 256;
    const MATCH_REGEX            = 512;

    const MATCH_CASE_INSENSITIVE = 1024;

    const MATCH_RECURSE          = 2048;
    const MATCH_NULL             = 4096;
    const MATCH_EMPTY            = 8192;
    const MATCH_SINGLE           = 16384;
    const MATCH_TRIM             = 32768;


    /**
     * Match actions
     */
    protected const MATCH_ACTION_RETURN_VALUES      = 1;
    protected const MATCH_ACTION_RETURN_KEYS        = 2;
    protected const MATCH_ACTION_RETURN_NEEDLES     = 3;
    protected const MATCH_ACTION_RETURN_NOT_VALUES  = 4;
    protected const MATCH_ACTION_RETURN_NOT_KEYS    = 5;
    protected const MATCH_ACTION_RETURN_NOT_NEEDLES = 6;
    protected const MATCH_ACTION_DELETE             = 7;


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
     * @param int  $options
     * @param bool $allow_recurse
     *
     * Supported match flags are:
     *
     * Utils::MATCH_CASE_INSENSITIVE  Will match needles for entries in case-insensitive mode.
     * Utils::MATCH_ALL               Will match needles for entries that contain all the specified needles.
     * Utils::MATCH_ANY               Will match needles for entries that contain any of the specified needles.
     * Utils::MATCH_STARTS_WITH       Will match needles for entries that start with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_ENDS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_ENDS_WITH         Will match needles for entries that end with the specified needles. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_CONTAINS,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_CONTAINS          Will match needles for entries that contain the specified needles anywhere.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_FULL, and Utils::MATCH_REGEX.
     * Utils::MATCH_RECURSE           Will recurse into arrays, if encountered.
     * Utils::MATCH_NOT               Will match needles for entries that do NOT match the needle.
     * Utils::MATCH_STRICT            Will match needles for entries that match the needle strict (so 0 does NOT match
     *                                "0", "" does NOT match 0, etc.).
     * Utils::MATCH_FULL              Will match needles for entries that fully match the needle. Mutually
     *                                exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_REGEX.
     * Utils::MATCH_REGEX             Will match needles for entries that match the specified regular expression.
     *                                Mutually exclusive with Utils::MATCH_STARTS_WITH, Utils::MATCH_ENDS_WITH,
     *                                Utils::MATCH_CONTAINS, and Utils::MATCH_FULL.
     * Utils::MATCH_EMPTY             Will match empty values instead of ignoring them. NOTE: Empty values may be
     *                                ignored while NULL values are still matched using the MATCH_NULL flag
     * Utils::MATCH_NULL              Will match NULL values instead of ignoring them. NOTE: NULL values may be
     *                                ignored while non-NULL empty values are still matched using the MATCH_EMPTY flag
     * Utils::MATCH_REQUIRE           Requires at least one result
     * Utils::MATCH_SINGLE            Will match only a single entry for the executed action (return, remove, etc.)
     *
     * @return array
     */
    protected static function decodeMatchFlags(int $options, bool $allow_recurse): array
    {
        // Decode options
        $return             = [];
        $return['no_case']  = (bool) ($options & self::MATCH_CASE_INSENSITIVE);
        $return['all']      = (bool) ($options & self::MATCH_ALL);
        $return['any']      = (bool) ($options & self::MATCH_ANY);
        $return['require']  = (bool) ($options & self::MATCH_REQUIRE);
        $return['start']    = (bool) ($options & self::MATCH_STARTS_WITH);
        $return['end']      = (bool) ($options & self::MATCH_ENDS_WITH);
        $return['contains'] = (bool) ($options & self::MATCH_CONTAINS);
        $return['recurse']  = (bool) ($options & self::MATCH_RECURSE);
        $return['not']      = (bool) ($options & self::MATCH_NOT);
        $return['strict']   = (bool) ($options & self::MATCH_STRICT);
        $return['full']     = (bool) ($options & self::MATCH_FULL);
        $return['regex']    = (bool) ($options & self::MATCH_REGEX);
        $return['empty']    = (bool) ($options & self::MATCH_EMPTY);
        $return['null']     = (bool) ($options & self::MATCH_NULL);
        $return['single']   = (bool) ($options & self::MATCH_SINGLE);
        $return['trim']     = (bool) ($options & self::MATCH_TRIM);

        // Validate options
        if ($return['full']) {
            $return['match_mode'] = 'full';

            if ($return['start'] or $return['end'] or $return['contains'] or $return['regex']) {
                $mutually = true;
            }

        } else {
            if ($return['strict']) {
                throw new OutOfBoundsException(tr('The MATCH_STRICT option can only be used with MATCH_FULL'));
            }

            if ($return['start']) {
                $return['match_mode'] = 'start';

                if ($return['end'] or $return['contains'] or $return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['end']) {
                $return['match_mode'] = 'end';

                if ($return['contains'] or $return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['contains']) {
                $return['match_mode'] = 'contains';

                if ($return['regex']) {
                    $mutually = true;
                }

            } elseif ($return['regex']){
                $return['match_mode'] = 'regex';

            } else {
                // Default to full matching
                $return['full']       = true;
                $return['match_mode'] = 'full';
            }
        }

        if (isset($mutually)) {
            throw new OutOfBoundsException(tr('Cannot mix location flags MATCH_STARTS_WITH, MATCH_ENDS_WITH, MATCH_CONTAINS, MATCH_FULL, or MATCH_REGEX, they are mutually exclusive'));
        }

        if ($return['all']) {
            if ($return['any']) {
                throw new OutOfBoundsException(tr('Cannot mix combination flags MATCH_ALL with MATCH_ANY, they are mutually exclusive'));
            }

        } elseif ($return['not']) {
            if (!$return['any']) {
                // MATCH_NOT defaults to MATCH_ALL
                $return['all'] = true;
            }

        } else {
            // Default to MATCH_ANY
            $return['any'] = true;
        }

        if ($return['recurse'] and !$allow_recurse) {
            throw new OutOfBoundsException(tr('Recursion matching not allowed'));
        }

        return $return;
    }


    /**
     * Checks specified needles that they have content and will ensure they are specified as an array
     *
     * @param DataListInterface|array|string|null $needles
     * @param array                               $flags
     *
     * @return array
     */
    protected static function prepareNeedles(DataListInterface|array|string|null $needles, array $flags): array
    {
        if (!$needles) {
            throw new OutOfBoundsException(tr('No needles specified'));
        }
        $needles = Arrays::force($needles);
        if ($flags['no_case'] or $flags['trim']) {
            // Trim and or make all needles lowercase strings?
            foreach ($needles as &$needle) {
                if ($flags['trim']) {
                    $needle = trim((string) $needle);
                }
                if ($flags['no_case']) {
                    $needle = strtolower((string) $needle);
                }
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
     * @param string|float|int|null $haystack
     * @param bool                  $match_no_case
     *
     * @return string
     */
    protected static function getTestValue(string|float|int|null $haystack, bool $match_no_case): string
    {
        if ($match_no_case) {
            return strtolower((string) $haystack);
        }

        return (string) $haystack;
    }


    /**
     * Returns true if the given haystack matches the given needles with the specified match flags
     *
     * @param Stringable|string|float|int $haystack
     * @param array                       $needles
     * @param array                       $flags
     *
     * @return bool
     */
    protected static function testStringMatchesNeedles(Stringable|string|float|int $haystack, array $needles, array $flags): bool
    {
        $result = true;
        // Compare to each needle
        foreach ($needles as $needle) {
            if ($flags['start']) {
                if (str_starts_with($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['any']) {
                        return true;
                    }

                } else {
                    $result = false;
                }

            } elseif ($flags['end']) {
                if (str_ends_with($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['any']) {
                        return true;
                    }

                } else {
                    $result = false;
                }

            } else {
                if (str_contains($haystack, $needle)) {
                    // This needle matched, any match is good enough
                    if ($flags['any']) {
                        return true;
                    }

                } else {
                    $result = false;
                }
            }
        }

        return $result;
    }
}
